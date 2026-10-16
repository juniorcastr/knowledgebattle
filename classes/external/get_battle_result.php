<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * External function get_battle_result.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        require_capability('mod/knowledgebattle:view', $context);

        // Only the players of the match (or users able to view all stats) may read it.
        $isplayer = ($USER->id == $match->player1_id) || (!empty($match->player2_id) && $USER->id == $match->player2_id);
        if (!$isplayer) {
            require_capability('mod/knowledgebattle:viewallstats', $context);
        }

        // Player names.
        $p1 = \core_user::get_user($match->player1_id);
        $p1_name = $p1 ? fullname($p1) : get_string('player', 'mod_knowledgebattle');

        if (!empty($match->is_bot_match) || empty($match->player2_id)) {
            $p2_name = get_string('bot_name', 'mod_knowledgebattle');
        } else {
            $p2 = \core_user::get_user($match->player2_id);
            $p2_name = $p2 ? fullname($p2) : get_string('player', 'mod_knowledgebattle');
        }

        $is_completed = ($match->status == 3);
        $winner_name = '';
        $is_draw = false;
        $user_is_winner = false;
        $points_earned = 0;

        $is_user_p1 = ($USER->id == $match->player1_id);
        $opponent_name = $is_user_p1 ? $p2_name : $p1_name;
        $user_score = $is_user_p1 ? (int)($match->p1_score ?? 0) : (int)($match->p2_score ?? 0);
        $opp_score = $is_user_p1 ? (int)($match->p2_score ?? 0) : (int)($match->p1_score ?? 0);
        $user_time = $is_user_p1 ? (int)($match->p1_time_ms ?? 0) : (int)($match->p2_time_ms ?? 0);
        $opp_time = $is_user_p1 ? (int)($match->p2_time_ms ?? 0) : (int)($match->p1_time_ms ?? 0);

        if ($is_completed) {
            $is_draw = ($match->winner_id == 0);
            if ($is_draw) {
                $winner_name = get_string('draw', 'mod_knowledgebattle');
                $points_earned = (int)($battle->draw_points ?? 30);
            } else if ($match->winner_id == $match->player1_id) {
                $winner_name = $p1_name;
            } else if ($match->winner_id == $match->player2_id || $match->winner_id == -1) {
                $winner_name = $p2_name;
            }

            $user_is_winner = ($match->winner_id == $USER->id);
            if (!$is_draw) {
                $points_earned = $user_is_winner ? (int)($battle->win_points ?? 100) : (int)($battle->loss_points ?? -20);
            }
        }

        // Get questions review with user turns.
        $sql = "SELECT q.id, q.question_text, q.options_json, q.correct_index, q.explanation
                  FROM {knowledgebattle_match_questions} mq
                  JOIN {knowledgebattle_questions} q ON q.id = mq.questionid
                 WHERE mq.matchid = ?
              ORDER BY mq.question_order ASC";
        $questions_records = $DB->get_records_sql($sql, [$match->id]);

        $user_turns = $DB->get_records('knowledgebattle_turns', [
            'matchid' => $match->id,
            'userid' => $USER->id
        ], '', 'questionid, selected_option, is_correct');

        $questions_list = [];
        $num = 1;
        foreach ($questions_records as $q) {
            $options = json_decode($q->options_json, true) ?: [];
            $turn = $user_turns[$q->id] ?? null;
            $user_answer = get_string('no_answer', 'mod_knowledgebattle');
            // Reveal correct answers and explanations only once the match is complete.
            $correct_answer = $is_completed ? ($options[$q->correct_index] ?? '') : '';
            $is_turn_correct = false;

            if ($turn) {
                $is_turn_correct = !empty($turn->is_correct);
                if ($turn->selected_option >= 0 && isset($options[$turn->selected_option])) {
                    $user_answer = $options[$turn->selected_option];
                } else if ($turn->selected_option == -1) {
                    $user_answer = get_string('time_expired', 'mod_knowledgebattle');
                }
            }

            $questions_list[] = [
                'num' => $num++,
                'question_text' => $q->question_text,
                'explanation' => $is_completed ? ($q->explanation ?? '') : '',
                'correct' => (bool)$is_turn_correct,
                'user_answer' => (string)$user_answer,
                'correct_answer' => (string)$correct_answer
            ];
        }

        $opponent_id = 0;
        if (!empty($match->is_bot_match) || $match->match_type == 3) {
            $opponent_id = 0;
        } else if ($match->player1_id == $USER->id) {
            $opponent_id = (int)($match->player2_id ?? 0);
        } else {
            $opponent_id = (int)$match->player1_id;
        }

        return [
            'status' => (int)$match->status,
            'is_completed' => (bool)$is_completed,
            'match_type' => (int)$match->match_type,
            'opponent_id' => (int)$opponent_id,
            'p1_name' => $p1_name,
            'p2_name' => $p2_name,
            'opponent_name' => $opponent_name,
            'p1_score' => (int)($match->p1_score ?? 0),
            'p2_score' => (int)($match->p2_score ?? 0),
            'p1_time_ms' => (int)($match->p1_time_ms ?? 0),
            'p2_time_ms' => (int)($match->p2_time_ms ?? 0),
            'user_score' => (int)$user_score,
            'opp_score' => (int)$opp_score,
            'user_time_ms' => (int)$user_time,
            'opp_time_ms' => (int)$opp_time,
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
            'status' => new external_value(PARAM_INT, 'Match status'),
            'is_completed' => new external_value(PARAM_BOOL, 'Is battle completed'),
            'match_type' => new external_value(PARAM_INT, 'Match type'),
            'opponent_id' => new external_value(PARAM_INT, 'Opponent ID relative to current user'),
            'p1_name' => new external_value(PARAM_TEXT, 'Player 1 name'),
            'p2_name' => new external_value(PARAM_TEXT, 'Player 2 name'),
            'opponent_name' => new external_value(PARAM_TEXT, 'Opponent name relative to current user'),
            'p1_score' => new external_value(PARAM_INT, 'Player 1 score'),
            'p2_score' => new external_value(PARAM_INT, 'Player 2 score'),
            'p1_time_ms' => new external_value(PARAM_INT, 'Player 1 time'),
            'p2_time_ms' => new external_value(PARAM_INT, 'Player 2 time'),
            'user_score' => new external_value(PARAM_INT, 'User score'),
            'opp_score' => new external_value(PARAM_INT, 'Opponent score'),
            'user_time_ms' => new external_value(PARAM_INT, 'User total time'),
            'opp_time_ms' => new external_value(PARAM_INT, 'Opponent total time'),
            'winner_id' => new external_value(PARAM_INT, 'Winner ID'),
            'winner_name' => new external_value(PARAM_TEXT, 'Winner name'),
            'is_draw' => new external_value(PARAM_BOOL, 'Is draw'),
            'points_earned' => new external_value(PARAM_INT, 'Points earned'),
            'user_is_winner' => new external_value(PARAM_BOOL, 'User is winner'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'num' => new external_value(PARAM_INT, 'Question number'),
                    'question_text' => new external_value(PARAM_RAW, 'Question text'),
                    'explanation' => new external_value(PARAM_RAW, 'Explanation text'),
                    'correct' => new external_value(PARAM_BOOL, 'Did user get it correct'),
                    'user_answer' => new external_value(PARAM_RAW, 'User answer text'),
                    'correct_answer' => new external_value(PARAM_RAW, 'Correct answer text')
                ])
            )
        ]);
    }
}
