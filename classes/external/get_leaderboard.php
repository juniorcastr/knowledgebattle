<?php
/**
 * External function get_leaderboard.
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

class get_leaderboard extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'battleid' => new external_value(PARAM_INT, 'Battle ID'),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 20)
        ]);
    }

    public static function execute($battleid, $page = 0, $perpage = 20) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid,
            'page' => $page,
            'perpage' => $perpage
        ]);

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Check ranking visibility setting.
        $visibility = $battle->ranking_visibility ?? 1;
        $is_teacher = has_capability('mod/knowledgebattle:viewallstats', $context);

        if ($visibility == 3 && !$is_teacher) {
            throw new \moodle_exception('nopermissions', 'error', '', 'view leaderboard');
        }

        $limit = $params['perpage'];
        $offset = $params['page'] * $params['perpage'];

        if ($visibility == 2 && !$is_teacher) {
            $limit = min($limit, 10);
            $offset = 0;
        }

        $total_count = $DB->count_records('knowledgebattle_user_stats', ['battleid' => $battle->id]);

        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT s.*, {$userfields}
                  FROM {knowledgebattle_user_stats} s
                  JOIN {user} u ON u.id = s.userid
                 WHERE s.battleid = ?
              ORDER BY s.current_points DESC, s.wins DESC, s.matches_played ASC";

        $records = $DB->get_records_sql($sql, [$battle->id], $offset, $limit);

        $rankings = [];
        $rank = $offset + 1;

        foreach ($records as $rec) {
            $name = fullname($rec);
            // Anonymize if top10 only visibility and not teacher and not own record.
            if ($visibility == 2 && !$is_teacher && $rec->userid != $USER->id) {
                $name = get_string('player', 'mod_knowledgebattle') . ' #' . $rank;
            }

            $rankings[] = [
                'rank' => (int)$rank,
                'userid' => (int)$rec->userid,
                'fullname' => $name,
                'current_points' => (int)$rec->current_points,
                'wins' => (int)$rec->wins,
                'losses' => (int)$rec->losses,
                'draws' => (int)$rec->draws,
                'matches_played' => (int)$rec->matches_played,
                'current_streak' => (int)$rec->current_streak,
                'max_streak' => (int)$rec->max_streak
            ];
            $rank++;
        }

        $user_rank = battle_manager::get_user_rank($battle->id, $USER->id);

        return [
            'rankings' => $rankings,
            'total_count' => (int)$total_count,
            'user_rank' => (int)$user_rank
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'rankings' => new external_multiple_structure(
                new external_single_structure([
                    'rank' => new external_value(PARAM_INT, 'Rank'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                    'current_points' => new external_value(PARAM_INT, 'Points'),
                    'wins' => new external_value(PARAM_INT, 'Wins'),
                    'losses' => new external_value(PARAM_INT, 'Losses'),
                    'draws' => new external_value(PARAM_INT, 'Draws'),
                    'matches_played' => new external_value(PARAM_INT, 'Matches played'),
                    'current_streak' => new external_value(PARAM_INT, 'Current streak'),
                    'max_streak' => new external_value(PARAM_INT, 'Max streak')
                ])
            ),
            'total_count' => new external_value(PARAM_INT, 'Total users'),
            'user_rank' => new external_value(PARAM_INT, 'User rank')
        ]);
    }
}
