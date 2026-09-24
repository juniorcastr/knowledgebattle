<?php
/**
 * Privacy API provider for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider,
                        \core_privacy\local\request\plugin\provider,
                        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table('knowledgebattle_matches', [
            'player1_id' => 'privacy:metadata:knowledgebattle_matches:player1_id',
            'player2_id' => 'privacy:metadata:knowledgebattle_matches:player2_id',
            'winner_id' => 'privacy:metadata:knowledgebattle_matches:winner_id',
            'timecreated' => 'privacy:metadata:timecreated',
            'timecompleted' => 'privacy:metadata:timecompleted',
        ], 'privacy:metadata:knowledgebattle_matches');

        $collection->add_database_table('knowledgebattle_turns', [
            'userid' => 'privacy:metadata:knowledgebattle_turns:userid',
            'answer' => 'privacy:metadata:knowledgebattle_turns:answer',
            'is_correct' => 'privacy:metadata:knowledgebattle_turns:is_correct',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:knowledgebattle_turns');

        $collection->add_database_table('knowledgebattle_user_stats', [
            'userid' => 'privacy:metadata:knowledgebattle_user_stats:userid',
            'matches_played' => 'privacy:metadata:knowledgebattle_user_stats:matches_played',
            'wins' => 'privacy:metadata:knowledgebattle_user_stats:wins',
            'losses' => 'privacy:metadata:knowledgebattle_user_stats:losses',
            'draws' => 'privacy:metadata:knowledgebattle_user_stats:draws',
            'current_points' => 'privacy:metadata:knowledgebattle_user_stats:current_points',
        ], 'privacy:metadata:knowledgebattle_user_stats');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        // In a full implementation, you would query DB and add contexts where the user has data.
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        // Export user data
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        // Delete all user data in context
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        // Delete data for user in contexts
    }

    public static function get_users_in_context(userlist $userlist) {
        // Get users with data in context
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        // Delete data for users
    }
}
