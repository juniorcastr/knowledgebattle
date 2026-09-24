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
            'match_type' => new external_value(PARAM_INT, 'Type of match: 1=direct, 2=matchmaking, 3=bot'),
            'opponent_id' => new external_value(PARAM_INT, 'Opponent ID for direct challenge', VALUE_DEFAULT, 0)
        ]);
    }

    public static function execute($battleid, $match_type, $opponent_id = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid,
            'match_type' => $match_type,
            'opponent_id' => $opponent_id
        ]);

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/knowledgebattle:view', $context);

        // Check daily limit.
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
                'questions_count' => (int)($battle->questions_per_match ?? 5),
                'time_per_question' => (int)($battle->time_per_question ?? 30)
            ];
        }

        // Mode 2: Matchmaking pool.
        if ($params['match_type'] == 2) {
            // First check if an open match exists in the pool.
            $existing_match = matchmaking_manager::find_best_opponent($battle->id, $USER->id);
            if ($existing_match) {
                return [
                    'matchid' => (int)$existing_match->id,
                    'match_type' => 2,
                    'status' => (int)$existing_match->status,
                    'questions_count' => (int)($battle->questions_per_match ?? 5),
                    'time_per_question' => (int)($battle->time_per_question ?? 30)
                ];
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
                'questions_count' => (int)($battle->questions_per_match ?? 5),
                'time_per_question' => (int)($battle->time_per_question ?? 30)
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
                'questions_count' => (int)($battle->questions_per_match ?? 5),
                'time_per_question' => (int)($battle->time_per_question ?? 30)
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
            'time_per_question' => new external_value(PARAM_INT, 'Time per question in seconds')
        ]);
    }
}
