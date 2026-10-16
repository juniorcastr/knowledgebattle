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
 * Privacy API provider for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for Knowledge Battle.
 *
 * Personal data is stored in matches, turns and user statistics. Matches in which the
 * user took part are deleted as a whole on erasure, since a match cannot be kept without
 * one of its players.
 *
 * The requests sent to AI providers contain only course content (activity text and the
 * teacher's topic) and never any user data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('knowledgebattle_matches', [
            'player1_id' => 'privacy:metadata:knowledgebattle_matches:player1_id',
            'player2_id' => 'privacy:metadata:knowledgebattle_matches:player2_id',
            'winner_id' => 'privacy:metadata:knowledgebattle_matches:winner_id',
            'p1_score' => 'privacy:metadata:knowledgebattle_matches:p1_score',
            'p2_score' => 'privacy:metadata:knowledgebattle_matches:p2_score',
            'p1_time_ms' => 'privacy:metadata:knowledgebattle_matches:p1_time_ms',
            'p2_time_ms' => 'privacy:metadata:knowledgebattle_matches:p2_time_ms',
            'timecreated' => 'privacy:metadata:timecreated',
            'timecompleted' => 'privacy:metadata:timecompleted',
        ], 'privacy:metadata:knowledgebattle_matches');

        $collection->add_database_table('knowledgebattle_turns', [
            'userid' => 'privacy:metadata:knowledgebattle_turns:userid',
            'selected_option' => 'privacy:metadata:knowledgebattle_turns:selected_option',
            'is_correct' => 'privacy:metadata:knowledgebattle_turns:is_correct',
            'response_time_ms' => 'privacy:metadata:knowledgebattle_turns:response_time_ms',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:knowledgebattle_turns');

        $collection->add_database_table('knowledgebattle_user_stats', [
            'userid' => 'privacy:metadata:knowledgebattle_user_stats:userid',
            'matches_played' => 'privacy:metadata:knowledgebattle_user_stats:matches_played',
            'wins' => 'privacy:metadata:knowledgebattle_user_stats:wins',
            'losses' => 'privacy:metadata:knowledgebattle_user_stats:losses',
            'draws' => 'privacy:metadata:knowledgebattle_user_stats:draws',
            'current_points' => 'privacy:metadata:knowledgebattle_user_stats:current_points',
            'current_streak' => 'privacy:metadata:knowledgebattle_user_stats:current_streak',
            'max_streak' => 'privacy:metadata:knowledgebattle_user_stats:max_streak',
        ], 'privacy:metadata:knowledgebattle_user_stats');

        // Challenge and battle result notifications.
        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');

        return $collection;
    }

    /**
     * Returns the contexts that hold data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {knowledgebattle} kb ON kb.id = cm.instance
             LEFT JOIN {knowledgebattle_matches} mt ON mt.battleid = kb.id
                       AND (mt.player1_id = :userid1 OR mt.player2_id = :userid2 OR mt.winner_id = :userid3)
             LEFT JOIN {knowledgebattle_user_stats} us ON us.battleid = kb.id AND us.userid = :userid4
                 WHERE mt.id IS NOT NULL OR us.id IS NOT NULL";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'knowledgebattle',
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
            'userid4' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Returns the users that hold data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $battleid = self::get_battleid($context);
        if (!$battleid) {
            return;
        }

        $userlist->add_from_sql('player1_id',
            "SELECT player1_id FROM {knowledgebattle_matches} WHERE battleid = :battleid", ['battleid' => $battleid]);
        $userlist->add_from_sql('player2_id',
            "SELECT player2_id FROM {knowledgebattle_matches} WHERE battleid = :battleid AND player2_id > 0",
            ['battleid' => $battleid]);
        $userlist->add_from_sql('userid',
            "SELECT userid FROM {knowledgebattle_user_stats} WHERE battleid = :battleid", ['battleid' => $battleid]);
    }

    /**
     * Exports the data of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $battleid = self::get_battleid($context);
            if (!$battleid) {
                continue;
            }

            // Generic activity data.
            helper::export_context_files($context, $contextlist->get_user());

            // Statistics.
            $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battleid, 'userid' => $userid]);
            if ($stats) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:stats', 'mod_knowledgebattle')],
                    (object) [
                        'matches_played' => $stats->matches_played,
                        'wins' => $stats->wins,
                        'losses' => $stats->losses,
                        'draws' => $stats->draws,
                        'current_points' => $stats->current_points,
                        'current_streak' => $stats->current_streak,
                        'max_streak' => $stats->max_streak,
                        'last_battle_date' => transform::datetime($stats->last_battle_date),
                    ]
                );
            }

            // Matches and turns.
            $matches = $DB->get_records_select('knowledgebattle_matches',
                'battleid = :battleid AND (player1_id = :u1 OR player2_id = :u2 OR winner_id = :u3)',
                ['battleid' => $battleid, 'u1' => $userid, 'u2' => $userid, 'u3' => $userid], 'id ASC');
            foreach ($matches as $match) {
                $isp1 = ($match->player1_id == $userid);
                $turns = $DB->get_records('knowledgebattle_turns',
                    ['matchid' => $match->id, 'userid' => $userid], 'id ASC');
                $turnsdata = [];
                foreach ($turns as $turn) {
                    $turnsdata[] = (object) [
                        'questionid' => $turn->questionid,
                        'selected_option' => $turn->selected_option,
                        'is_correct' => transform::yesno($turn->is_correct),
                        'response_time_ms' => $turn->response_time_ms,
                        'timecreated' => transform::datetime($turn->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:path:matches', 'mod_knowledgebattle'), $match->id],
                    (object) [
                        'was_player1' => transform::yesno($isp1),
                        'won' => transform::yesno($match->winner_id == $userid),
                        'your_score' => $isp1 ? $match->p1_score : $match->p2_score,
                        'opponent_score' => $isp1 ? $match->p2_score : $match->p1_score,
                        'your_time_ms' => $isp1 ? $match->p1_time_ms : $match->p2_time_ms,
                        'status' => $match->status,
                        'timecreated' => transform::datetime($match->timecreated),
                        'timecompleted' => transform::datetime($match->timecompleted),
                        'turns' => $turnsdata,
                    ]
                );
            }
        }
    }

    /**
     * Deletes all user data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $battleid = self::get_battleid($context);
        if (!$battleid) {
            return;
        }

        $matchids = $DB->get_fieldset_select('knowledgebattle_matches', 'id', 'battleid = ?', [$battleid]);
        self::delete_matches($matchids);
        $DB->delete_records('knowledgebattle_user_stats', ['battleid' => $battleid]);
    }

    /**
     * Deletes the data of a user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $battleid = self::get_battleid($context);
            if ($battleid) {
                self::delete_user_in_battle($battleid, [$userid]);
            }
        }
    }

    /**
     * Deletes the data of several users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $battleid = self::get_battleid($context);
        if ($battleid && $userlist->get_userids()) {
            self::delete_user_in_battle($battleid, $userlist->get_userids());
        }
    }

    /**
     * Returns the activity id for a module context.
     *
     * @param \context_module $context
     * @return int|null
     */
    private static function get_battleid(\context_module $context): ?int {
        global $DB;

        $sql = "SELECT cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $id = $DB->get_field_sql($sql, ['modname' => 'knowledgebattle', 'cmid' => $context->instanceid]);
        return $id ? (int) $id : null;
    }

    /**
     * Deletes matches (with their questions and turns) by id.
     *
     * @param int[] $matchids
     */
    private static function delete_matches(array $matchids): void {
        global $DB;

        if (empty($matchids)) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($matchids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('knowledgebattle_turns', "matchid $insql", $inparams);
        $DB->delete_records_select('knowledgebattle_match_questions', "matchid $insql", $inparams);
        $DB->delete_records_select('knowledgebattle_matches', "id $insql", $inparams);
    }

    /**
     * Deletes matches, turns and statistics of users within one activity.
     *
     * @param int $battleid
     * @param int[] $userids
     */
    private static function delete_user_in_battle(int $battleid, array $userids): void {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        // Named parameters cannot be reused, so each IN clause gets its own prefix.
        [$insql2, $inparams2] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'pb');
        $select = "battleid = :battleid AND (player1_id $insql OR player2_id $insql2)";
        $matchids = $DB->get_fieldset_select('knowledgebattle_matches', 'id', $select,
            ['battleid' => $battleid] + $inparams + $inparams2);

        self::delete_matches($matchids);

        [$insql3, $inparams3] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'pc');
        $DB->delete_records_select('knowledgebattle_user_stats', "battleid = :battleid AND userid $insql3",
            ['battleid' => $battleid] + $inparams3);
    }
}
