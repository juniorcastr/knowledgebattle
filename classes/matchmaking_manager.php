<?php
/**
 * Matchmaking Manager for knowledgebattle
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class matchmaking_manager {

    /**
     * Finds the best opponent using a simplified ELO matching logic.
     *
     * @param int $battleid
     * @param int $userid
     * @return object|null
     */
    public static function find_best_opponent(int $battleid, int $userid): ?object {
        global $DB;

        $user_stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battleid, 'userid' => $userid]);
        $user_points = $user_stats ? $user_stats->current_points : 0;

        $sql = "SELECT m.*
                  FROM {knowledgebattle_matches} m
             LEFT JOIN {knowledgebattle_user_stats} s ON m.player1_id = s.userid AND s.battleid = m.battleid
                 WHERE m.battleid = :battleid
                   AND m.status = 2 
                   AND m.match_type = 2 
                   AND m.player2_id IS NULL 
                   AND m.player1_id != :userid
              ORDER BY ABS(COALESCE(s.current_points, 0) - :userpoints) ASC, m.timecreated ASC";
              
        $matches = $DB->get_records_sql($sql, [
            'battleid' => $battleid,
            'userid' => $userid,
            'userpoints' => $user_points
        ], 0, 1);

        if ($matches) {
            $match = reset($matches);
            
            // Lock the match
            $match->player2_id = $userid;
            $match->status = 1;
            $match->timemodified = time();
            $DB->update_record('knowledgebattle_matches', $match);
            
            return $match;
        }

        return null;
    }

    /**
     * Adds a match to the matchmaking pool.
     * The record naturally acts as a pool entry.
     *
     * @param object $match
     */
    public static function add_to_pool(object $match): void {
        global $DB;
        $match->status = 2; // waiting
        $match->timemodified = time();
        $DB->update_record('knowledgebattle_matches', $match);
    }

    /**
     * Removes expired entries from the pool.
     *
     * @param int $battleid
     * @param int $max_age_hours
     * @return int
     */
    public static function remove_expired_pool_entries(int $battleid, int $max_age_hours = 72): int {
        global $DB;
        
        $cutoff = time() - ($max_age_hours * 3600);
        
        $sql = "SELECT id FROM {knowledgebattle_matches} 
                 WHERE battleid = ? AND status = 2 AND match_type = 2 AND player2_id IS NULL AND timecreated < ?";
                 
        $expired = $DB->get_records_sql($sql, [$battleid, $cutoff]);
        $count = 0;
        
        foreach ($expired as $match) {
            $match->status = 5; // cancelled
            $match->timemodified = time();
            $DB->update_record('knowledgebattle_matches', $match);
            $count++;
        }
        
        return $count;
    }
}
