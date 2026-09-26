<?php
/**
 * External function submit_answer.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use mod_knowledgebattle\battle_manager;
use mod_knowledgebattle\matchmaking_manager;

defined('MOODLE_INTERNAL') || die();

class submit_answer extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'matchid' => new external_value(PARAM_INT, 'Match ID'),
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'selected_option' => new external_value(PARAM_INT, 'Selected option (shuffled index)'),
            'response_time_ms' => new external_value(PARAM_INT, 'Response time in ms', VALUE_DEFAULT, 0)
        ]);
    }

    public static function execute($matchid, $questionid, $selected_option, $response_time_ms = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'matchid' => $matchid,
            'questionid' => $questionid,
            'selected_option' => $selected_option,
            'response_time_ms' => $response_time_ms
        ]);

        $match = $DB->get_record('knowledgebattle_matches', ['id' => $params['matchid']], '*', MUST_EXIST);
        $battle = $DB->get_record('knowledgebattle', ['id' => $match->battleid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        if ($match->player1_id != $USER->id && $match->player2_id != $USER->id) {
            throw new \moodle_exception('notyourmatch', 'mod_knowledgebattle');
        }

        // Validate question belongs to this match.
        $mq = $DB->get_record('knowledgebattle_match_questions', [
            'matchid' => $match->id,
            'questionid' => $params['questionid']
        ], '*', MUST_EXIST);

        // Check if already answered.
        $existing_turn = $DB->get_record('knowledgebattle_turns', [
            'matchid' => $match->id,
            'userid' => $USER->id,
            'questionid' => $params['questionid']
        ]);
        if ($existing_turn) {
            throw new \moodle_exception('alreadyanswered', 'mod_knowledgebattle');
        }

        $question = $DB->get_record('knowledgebattle_questions', ['id' => $params['questionid']], '*', MUST_EXIST);
        $options = json_decode($question->options_json, true) ?: [];
        $total_options = count($options);

        // Unshuffle selected option to compare with original correct_index.
        $real_option = battle_manager::unshuffle_option(
            $USER->id,
            $params['questionid'],
            $params['selected_option'],
            $total_options > 0 ? $total_options : 4
        );

        $is_correct = ($real_option == $question->correct_index) ? 1 : 0;

        // Cap client response time with tolerance.
        $max_allowed_time = (($battle->time_per_question ?? 30) * 1000) + 1500;
        $actual_time = min((int)$params['response_time_ms'], $max_allowed_time);
        if ($actual_time <= 0) {
            $actual_time = 1000;
        }

        $turn = new \stdClass();
        $turn->matchid = $match->id;
        $turn->userid = $USER->id;
        $turn->questionid = $question->id;
        $turn->selected_option = $real_option;
        $turn->is_correct = $is_correct;
        $turn->response_time_ms = $actual_time;
        $turn->timecreated = time();
        $turn->id = $DB->insert_record('knowledgebattle_turns', $turn);

        // Trigger answer event.
        if (class_exists('\mod_knowledgebattle\event\answer_submitted')) {
            $event = \mod_knowledgebattle\event\answer_submitted::create([
                'objectid' => $turn->id,
                'context' => $context,
                'other' => [
                    'matchid' => $match->id,
                    'questionid' => $question->id,
                    'is_correct' => $is_correct
                ]
            ]);
            $event->trigger();
        }

        // Check if all questions have been answered by this player.
        $total_questions = $battle->questions_per_match ?? 5;
        $user_turns_count = $DB->count_records('knowledgebattle_turns', [
            'matchid' => $match->id,
            'userid' => $USER->id
        ]);
        $is_last_question = ($user_turns_count >= $total_questions);

        if ($is_last_question) {
            // Case 1: Bot match.
            if (!empty($match->is_bot_match) || $match->match_type == 3) {
                battle_manager::simulate_bot_answers($match, $battle);
            } else {
                // Case 2: Human duel.
                $p1_done = $DB->count_records('knowledgebattle_turns', [
                    'matchid' => $match->id,
                    'userid' => $match->player1_id
                ]) >= $total_questions;

                $p2_done = false;
                if (!empty($match->player2_id)) {
                    $p2_done = $DB->count_records('knowledgebattle_turns', [
                        'matchid' => $match->id,
                        'userid' => $match->player2_id
                    ]) >= $total_questions;
                }

                if ($p1_done && $p2_done) {
                    battle_manager::finalize_match($match, $battle);
                } else if ($p1_done && empty($match->player2_id)) {
                    // Match sits in matchmaking pool waiting for player 2.
                    matchmaking_manager::add_to_pool($match);
                } else {
                    $match->status = 2; // p1_finished / waiting opponent
                    $match->timemodified = time();
                    $DB->update_record('knowledgebattle_matches', $match);
                }
            }
        }

        // Fetch refreshed match record.
        $refreshed_match = $DB->get_record('knowledgebattle_matches', ['id' => $match->id]);

        // Calculate correct index in shuffled order for user's feedback.
        $shuffled_result = battle_manager::get_shuffled_options($USER->id, $question->id, $options);
        $correct_shuffled_index = $shuffled_result['map'][$question->correct_index] ?? 0;

        return [
            'is_correct' => (bool)$is_correct,
            'correct_option_index' => (int)$correct_shuffled_index,
            'explanation' => $question->explanation ?? '',
            'is_last_question' => (bool)$is_last_question,
            'match_status' => (int)($refreshed_match->status ?? $match->status)
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'is_correct' => new external_value(PARAM_BOOL, 'Is correct'),
            'correct_option_index' => new external_value(PARAM_INT, 'Correct option index in user shuffled layout'),
            'explanation' => new external_value(PARAM_RAW, 'Explanation text'),
            'is_last_question' => new external_value(PARAM_BOOL, 'Is last question'),
            'match_status' => new external_value(PARAM_INT, 'Match status')
        ]);
    }
}
