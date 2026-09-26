<?php
/**
 * External function get_user_stats.
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

class get_user_stats extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'battleid' => new external_value(PARAM_INT, 'Battle ID')
        ]);
    }

    public static function execute($battleid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid
        ]);

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $stats = $DB->get_record('knowledgebattle_user_stats', [
            'battleid' => $battle->id,
            'userid' => $USER->id
        ]);

        $current_points = $stats ? (int)$stats->current_points : 0;
        $matches_played = $stats ? (int)$stats->matches_played : 0;
        $wins = $stats ? (int)$stats->wins : 0;
        $losses = $stats ? (int)$stats->losses : 0;
        $draws = $stats ? (int)$stats->draws : 0;
        $current_streak = $stats ? (int)$stats->current_streak : 0;
        $max_streak = $stats ? (int)$stats->max_streak : 0;

        // Daily battle calculation.
        $today = strtotime('today', time());
        $daily_battles_today = 0;
        if ($stats && $stats->last_battle_date == $today) {
            $daily_battles_today = (int)$stats->daily_battles_count;
        }

        $win_rate = $matches_played > 0 ? round(($wins / $matches_played) * 100) . '%' : '0%';
        $rank_position = battle_manager::get_user_rank($battle->id, $USER->id);

        // Fetch last 10 matches involving current user.
        $sql = "SELECT m.*
                  FROM {knowledgebattle_matches} m
                 WHERE m.battleid = ?
                   AND (m.player1_id = ? OR m.player2_id = ?)
                   AND m.status = 3
              ORDER BY m.timecompleted DESC";

        $recent_matches_records = $DB->get_records_sql($sql, [$battle->id, $USER->id, $USER->id], 0, 10);
        $recent_matches = [];

        foreach ($recent_matches_records as $m) {
            $opponent_id = ($m->player1_id == $USER->id) ? $m->player2_id : $m->player1_id;
            $opponent_name = '';

            if (empty($opponent_id) || !empty($m->is_bot_match)) {
                $opponent_name = get_string('challenge_bot', 'mod_knowledgebattle');
            } else {
                $opp_user = \core_user::get_user($opponent_id);
                $opponent_name = $opp_user ? fullname($opp_user) : get_string('player', 'mod_knowledgebattle');
            }

            $result = '';
            if ($m->winner_id == 0) {
                $result = get_string('draw', 'mod_knowledgebattle');
            } else if ($m->winner_id == $USER->id) {
                $result = get_string('you_won', 'mod_knowledgebattle');
            } else {
                $result = get_string('you_lost', 'mod_knowledgebattle');
            }

            $recent_matches[] = [
                'matchid' => (int)$m->id,
                'opponent_name' => $opponent_name,
                'result' => $result
            ];
        }

        return [
            'current_points' => $current_points,
            'matches_played' => $matches_played,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'win_rate' => $win_rate,
            'current_streak' => $current_streak,
            'max_streak' => $max_streak,
            'daily_battles_today' => $daily_battles_today,
            'daily_battles_limit' => (int)($battle->max_daily_battles ?? 5),
            'rank_position' => (int)$rank_position,
            'recent_matches' => $recent_matches
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'current_points' => new external_value(PARAM_INT, 'Points'),
            'matches_played' => new external_value(PARAM_INT, 'Matches played'),
            'wins' => new external_value(PARAM_INT, 'Wins'),
            'losses' => new external_value(PARAM_INT, 'Losses'),
            'draws' => new external_value(PARAM_INT, 'Draws'),
            'win_rate' => new external_value(PARAM_TEXT, 'Win rate percentage'),
            'current_streak' => new external_value(PARAM_INT, 'Current streak'),
            'max_streak' => new external_value(PARAM_INT, 'Max streak'),
            'daily_battles_today' => new external_value(PARAM_INT, 'Battles played today'),
            'daily_battles_limit' => new external_value(PARAM_INT, 'Daily limit'),
            'rank_position' => new external_value(PARAM_INT, 'Rank position'),
            'recent_matches' => new external_multiple_structure(
                new external_single_structure([
                    'matchid' => new external_value(PARAM_INT, 'Match ID'),
                    'opponent_name' => new external_value(PARAM_TEXT, 'Opponent name'),
                    'result' => new external_value(PARAM_TEXT, 'Result text')
                ])
            )
        ]);
    }
}
