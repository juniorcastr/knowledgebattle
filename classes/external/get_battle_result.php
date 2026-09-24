<?php
/**
 * External function get_battle_result.
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

defined('MOODLE_INTERNAL') || die();

class get_battle_result extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'matchid' => new external_value(PARAM_INT, 'Match ID')
        ]);
    }

    public static function execute($matchid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'matchid' => $matchid
        ]);

        $match = $DB->get_record('knowledgebattle_matches', ['id' => $params['matchid']], '*', MUST_EXIST);
        $battle = $DB->get_record('knowledgebattle', ['id' => $match->battleid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Player names.
        $p1 = \core_user::get_user($match->player1_id);
        $p1_name = $p1 ? fullname($p1) : get_string('player', 'mod_knowledgebattle');

        if (!empty($match->is_bot_match) || empty($match->player2_id)) {
            $p2_name = get_string('challenge_bot', 'mod_knowledgebattle');
        } else {
            $p2 = \core_user::get_user($match->player2_id);
            $p2_name = $p2 ? fullname($p2) : get_string('player', 'mod_knowledgebattle');
        }

        $winner_name = '';
        $is_draw = ($match->winner_id == 0);
        if ($is_draw) {
            $winner_name = get_string('draw', 'mod_knowledgebattle');
        } else if ($match->winner_id == $match->player1_id) {
            $winner_name = $p1_name;
        } else if ($match->winner_id == $match->player2_id || $match->winner_id == -1) {
            $winner_name = $p2_name;
        }

        $user_is_winner = ($match->winner_id == $USER->id);

        // Calculate points earned.
        $points_earned = 0;
        if ($is_draw) {
            $points_earned = (int)($battle->draw_points ?? 30);
        } else if ($user_is_winner) {
            $points_earned = (int)($battle->win_points ?? 100);
        } else {
            $points_earned = (int)($battle->loss_points ?? -20);
        }

        // Get questions review.
        $sql = "SELECT q.id, q.question_text, q.explanation
                  FROM {knowledgebattle_match_questions} mq
                  JOIN {knowledgebattle_questions} q ON q.id = mq.questionid
                 WHERE mq.matchid = ?
              ORDER BY mq.question_order ASC";
        $questions_records = $DB->get_records_sql($sql, [$match->id]);

        $questions_list = [];
        foreach ($questions_records as $q) {
            $questions_list[] = [
                'question_text' => $q->question_text,
                'explanation' => $q->explanation ?? ''
            ];
        }

        return [
            'p1_name' => $p1_name,
            'p2_name' => $p2_name,
            'p1_score' => (int)($match->p1_score ?? 0),
            'p2_score' => (int)($match->p2_score ?? 0),
            'p1_time_ms' => (int)($match->p1_time_ms ?? 0),
            'p2_time_ms' => (int)($match->p2_time_ms ?? 0),
            'winner_id' => (int)($match->winner_id ?? 0),
            'winner_name' => $winner_name,
            'is_draw' => (bool)$is_draw,
            'points_earned' => (int)$points_earned,
            'user_is_winner' => (bool)$user_is_winner,
            'questions' => $questions_list
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'p1_name' => new external_value(PARAM_TEXT, 'Player 1 name'),
            'p2_name' => new external_value(PARAM_TEXT, 'Player 2 name'),
            'p1_score' => new external_value(PARAM_INT, 'Player 1 score'),
            'p2_score' => new external_value(PARAM_INT, 'Player 2 score'),
            'p1_time_ms' => new external_value(PARAM_INT, 'Player 1 time'),
            'p2_time_ms' => new external_value(PARAM_INT, 'Player 2 time'),
            'winner_id' => new external_value(PARAM_INT, 'Winner ID'),
            'winner_name' => new external_value(PARAM_TEXT, 'Winner name'),
            'is_draw' => new external_value(PARAM_BOOL, 'Is draw'),
            'points_earned' => new external_value(PARAM_INT, 'Points earned'),
            'user_is_winner' => new external_value(PARAM_BOOL, 'User is winner'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'question_text' => new external_value(PARAM_RAW, 'Question text'),
                    'explanation' => new external_value(PARAM_RAW, 'Explanation text')
                ])
            )
        ]);
    }
}
