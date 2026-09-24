<?php
/**
 * External function start_battle.
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
use mod_knowledgebattle\notification_manager;

defined('MOODLE_INTERNAL') || die();

class start_battle extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'battleid' => new external_value(PARAM_INT, 'Battle instance ID'),
            'match_type' => new external_value(PARAM_INT, 'Type of match: 1=direct, 2=matchmaking, 3=bot', VALUE_DEFAULT, 1),
            'opponent_id' => new external_value(PARAM_INT, 'Opponent ID for direct challenge', VALUE_DEFAULT, 0),
            'matchid' => new external_value(PARAM_INT, 'Optional match ID to accept or resume', VALUE_DEFAULT, 0)
        ]);
    }

    public static function execute($battleid, $match_type = 1, $opponent_id = 0, $matchid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid,
            'match_type' => $match_type,
            'opponent_id' => $opponent_id,
            'matchid' => $matchid
        ]);

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/knowledgebattle:view', $context);

        $total_questions = (int)($battle->questions_per_match ?? 5);
        $time_per_question = (int)($battle->time_per_question ?? 30);

        // Case A: User is accepting or resuming an existing match directly by match ID.
        if (!empty($params['matchid'])) {
            $match = $DB->get_record('knowledgebattle_matches', [
                'id' => $params['matchid'],
                'battleid' => $battle->id
            ], '*', MUST_EXIST);

            // Verify participation authorization.
            $is_p1 = ($match->player1_id == $USER->id);
            $is_p2 = ($match->player2_id == $USER->id);
            $is_open_pool = ($match->match_type == 2 && empty($match->player2_id) && !$is_p1);

            if (!$is_p1 && !$is_p2 && !$is_open_pool) {
                throw new \moodle_exception('notyourmatch', 'mod_knowledgebattle');
            }

            if ($match->status == 3 || $match->status == 4 || $match->status == 5) {
                throw new \moodle_exception('error_match_already_completed', 'mod_knowledgebattle');
            }

            if (!empty($match->timeexpire) && $match->timeexpire < time()) {
                throw new \moodle_exception('error_battle_expired', 'mod_knowledgebattle');
            }

            // Assign as player 2 if taking open pool match.
            if ($is_open_pool) {
                $match->player2_id = $USER->id;
                $match->status = 1;
                $match->timemodified = time();
                $DB->update_record('knowledgebattle_matches', $match);
            } else if ($is_p2 && $match->status == 2) {
                // Player 2 is accepting the challenge after Player 1 finished.
                $match->status = 1;
                $match->timemodified = time();
                $DB->update_record('knowledgebattle_matches', $match);
            }

            $user_turns_count = $DB->count_records('knowledgebattle_turns', [
                'matchid' => $match->id,
                'userid' => $USER->id
            ]);

            if ($user_turns_count >= $total_questions) {
                throw new \moodle_exception('error_user_already_finished', 'mod_knowledgebattle');
            }

            return [
                'matchid' => (int)$match->id,
                'match_type' => (int)$match->match_type,
                'status' => (int)$match->status,
                'questions_count' => $total_questions,
                'time_per_question' => $time_per_question,
                'user_turns_count' => (int)$user_turns_count
            ];
        }

        // Check daily limit for new matches.
        if (!battle_manager::check_daily_limit($battle->id, $USER->id, $battle->max_daily_battles ?? 5)) {
            throw new \moodle_exception('error_daily_limit', 'mod_knowledgebattle');
        }

        // Mode 1: Direct challenge.
        if ($params['match_type'] == 1) {
            if (empty($params['opponent_id'])) {
                throw new \invalid_parameter_exception('Opponent ID required for direct challenge');
            }
            if ($params['opponent_id'] == $USER->id) {
                throw new \moodle_exception('error_invalid_answer', 'mod_knowledgebattle');
            }

            // Check if there is already an active challenge sent by opponent to current user.
            $existing_incoming = $DB->get_record_select(
                'knowledgebattle_matches',
                'battleid = ? AND match_type = 1 AND player1_id = ? AND player2_id = ? AND status IN (1, 2) AND timeexpire > ?',
                [$battle->id, $params['opponent_id'], $USER->id, time()],
                '*',
                IGNORE_MULTIPLE
            );
            if ($existing_incoming) {
                $user_turns = $DB->count_records('knowledgebattle_turns', [
                    'matchid' => $existing_incoming->id,
                    'userid' => $USER->id
                ]);
                if ($user_turns < $total_questions) {
                    if ($existing_incoming->status == 2) {
                        $existing_incoming->status = 1;
                        $existing_incoming->timemodified = time();
                        $DB->update_record('knowledgebattle_matches', $existing_incoming);
                    }
                    return [
                        'matchid' => (int)$existing_incoming->id,
                        'match_type' => 1,
                        'status' => (int)$existing_incoming->status,
                        'questions_count' => $total_questions,
                        'time_per_question' => $time_per_question,
                        'user_turns_count' => (int)$user_turns
                    ];
                }
            }

            // Check if current user already sent an active challenge to opponent.
            $existing_sent = $DB->get_record_select(
                'knowledgebattle_matches',
                'battleid = ? AND match_type = 1 AND player1_id = ? AND player2_id = ? AND status IN (1, 2) AND timeexpire > ?',
                [$battle->id, $USER->id, $params['opponent_id'], time()],
                '*',
                IGNORE_MULTIPLE
            );
            if ($existing_sent) {
                $user_turns = $DB->count_records('knowledgebattle_turns', [
                    'matchid' => $existing_sent->id,
                    'userid' => $USER->id
                ]);
                if ($user_turns < $total_questions) {
                    return [
                        'matchid' => (int)$existing_sent->id,
                        'match_type' => 1,
                        'status' => (int)$existing_sent->status,
                        'questions_count' => $total_questions,
                        'time_per_question' => $time_per_question,
                        'user_turns_count' => (int)$user_turns
                    ];
                } else {
                    throw new \moodle_exception('error_already_waiting_opponent', 'mod_knowledgebattle');
                }
            }

            $match = battle_manager::create_match($battle->id, $USER->id, 1, $params['opponent_id']);

            // Notify opponent of challenge.
            notification_manager::notify_challenge($match, $battle, $USER->id, $params['opponent_id']);

            if (class_exists('\mod_knowledgebattle\event\challenge_sent')) {
                $event = \mod_knowledgebattle\event\challenge_sent::create([
                    'objectid' => $match->id,
                    'context' => $context,
                    'other' => ['challengedid' => $params['opponent_id']]
                ]);
                $event->trigger();
            }

            return [
                'matchid' => (int)$match->id,
                'match_type' => 1,
                'status' => (int)$match->status,
                'questions_count' => $total_questions,
                'time_per_question' => $time_per_question,
                'user_turns_count' => 0
            ];
        }

        // Mode 2: Matchmaking pool.
        if ($params['match_type'] == 2) {
            // First check if an open match exists in the pool from another student.
            $existing_match = matchmaking_manager::find_best_opponent($battle->id, $USER->id);
            if ($existing_match) {
                return [
                    'matchid' => (int)$existing_match->id,
                    'match_type' => 2,
                    'status' => (int)$existing_match->status,
                    'questions_count' => $total_questions,
                    'time_per_question' => $time_per_question,
                    'user_turns_count' => 0
                ];
            }

            // Check if user already has an active pool match waiting for an opponent.
            $my_pool_match = $DB->get_record_select(
                'knowledgebattle_matches',
                'battleid = ? AND match_type = 2 AND player1_id = ? AND player2_id IS NULL AND status IN (1, 2) AND timeexpire > ?',
                [$battle->id, $USER->id, time()],
                '*',
                IGNORE_MULTIPLE
            );
            if ($my_pool_match) {
                $user_turns = $DB->count_records('knowledgebattle_turns', [
                    'matchid' => $my_pool_match->id,
                    'userid' => $USER->id
                ]);
                if ($user_turns < $total_questions) {
                    return [
                        'matchid' => (int)$my_pool_match->id,
                        'match_type' => 2,
                        'status' => (int)$my_pool_match->status,
                        'questions_count' => $total_questions,
                        'time_per_question' => $time_per_question,
                        'user_turns_count' => (int)$user_turns
                    ];
                }
            }

            // Create new match in pool (player 1 plays first, then leaves in pool).
            $match = battle_manager::create_match($battle->id, $USER->id, 2, null);

            if (class_exists('\mod_knowledgebattle\event\battle_started')) {
                $event = \mod_knowledgebattle\event\battle_started::create([
                    'objectid' => $match->id,
                    'context' => $context
                ]);
                $event->trigger();
            }

            return [
                'matchid' => (int)$match->id,
                'match_type' => 2,
                'status' => (int)$match->status,
                'questions_count' => $total_questions,
                'time_per_question' => $time_per_question,
                'user_turns_count' => 0
            ];
        }

        // Mode 3: AI Bot.
        if ($params['match_type'] == 3) {
            require_capability('mod/knowledgebattle:challengebot', $context);
            if (empty($battle->bot_enabled)) {
                throw new \moodle_exception('botdisabled', 'mod_knowledgebattle');
            }

            $match = battle_manager::create_match($battle->id, $USER->id, 3, null);

            if (class_exists('\mod_knowledgebattle\event\battle_started')) {
                $event = \mod_knowledgebattle\event\battle_started::create([
                    'objectid' => $match->id,
                    'context' => $context
                ]);
                $event->trigger();
            }

            return [
                'matchid' => (int)$match->id,
                'match_type' => 3,
                'status' => (int)$match->status,
                'questions_count' => $total_questions,
                'time_per_question' => $time_per_question,
                'user_turns_count' => 0
            ];
        }

        throw new \invalid_parameter_exception('Invalid match_type');
    }

    public static function execute_returns() {
        return new external_single_structure([
            'matchid' => new external_value(PARAM_INT, 'Match ID'),
            'match_type' => new external_value(PARAM_INT, 'Match type'),
            'status' => new external_value(PARAM_INT, 'Match status'),
            'questions_count' => new external_value(PARAM_INT, 'Number of questions'),
            'time_per_question' => new external_value(PARAM_INT, 'Time per question in seconds'),
            'user_turns_count' => new external_value(PARAM_INT, 'Number of turns already answered by this user', VALUE_DEFAULT, 0)
        ]);
    }
}
