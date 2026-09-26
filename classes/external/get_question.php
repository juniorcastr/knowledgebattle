<?php
/**
 * External function get_question.
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
use core_external\external_multiple_structure;
use mod_knowledgebattle\battle_manager;

defined('MOODLE_INTERNAL') || die();

class get_question extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'matchid' => new external_value(PARAM_INT, 'Match ID'),
            'question_number' => new external_value(PARAM_INT, 'Question number (1-based)')
        ]);
    }

    public static function execute($matchid, $question_number) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'matchid' => $matchid,
            'question_number' => $question_number
        ]);

        $match = $DB->get_record('knowledgebattle_matches', ['id' => $params['matchid']], '*', MUST_EXIST);
        $battle = $DB->get_record('knowledgebattle', ['id' => $match->battleid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Ensure user is an assigned player.
        if ($match->player1_id != $USER->id && $match->player2_id != $USER->id) {
            throw new \moodle_exception('notyourmatch', 'mod_knowledgebattle');
        }

        $sql = "SELECT mq.id, mq.questionid, q.question_text, q.options_json 
                  FROM {knowledgebattle_match_questions} mq
                  JOIN {knowledgebattle_questions} q ON q.id = mq.questionid
                 WHERE mq.matchid = ? AND mq.question_order = ?";
        $qdata = $DB->get_record_sql($sql, [$match->id, $params['question_number']], MUST_EXIST);

        $options = json_decode($qdata->options_json, true);
        if (!is_array($options)) {
            $options = [];
        }

        // Deterministic anti-cheat shuffle.
        $shuffle_result = battle_manager::get_shuffled_options($USER->id, $qdata->questionid, $options);
        $final_options = $shuffle_result['options'];
        $map = $shuffle_result['map']; // original_index => shuffled_index

        // Check if user already answered this question.
        $turn = $DB->get_record('knowledgebattle_turns', [
            'matchid' => $match->id,
            'userid' => $USER->id,
            'questionid' => $qdata->questionid
        ]);

        $selected_option = -1;
        if ($turn && isset($map[$turn->selected_option])) {
            $selected_option = (int)$map[$turn->selected_option];
        }

        $total_questions = $battle->questions_per_match ?? 5;
        $time_limit = $battle->time_per_question ?? 30;

        return [
            'questionid' => (int)$qdata->questionid,
            'question_number' => (int)$params['question_number'],
            'total_questions' => (int)$total_questions,
            'question_text' => $qdata->question_text,
            'options' => $final_options,
            'time_limit' => (int)$time_limit,
            'already_answered' => $turn ? true : false,
            'selected_option' => (int)$selected_option
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'question_number' => new external_value(PARAM_INT, 'Question number'),
            'total_questions' => new external_value(PARAM_INT, 'Total questions'),
            'question_text' => new external_value(PARAM_RAW, 'Question text'),
            'options' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Option text')
            ),
            'time_limit' => new external_value(PARAM_INT, 'Time limit per question in seconds'),
            'already_answered' => new external_value(PARAM_BOOL, 'Has user answered?'),
            'selected_option' => new external_value(PARAM_INT, 'Selected option (shuffled index)')
        ]);
    }
}
