<?php
/**
 * Task to check for expired matches and declare W.O.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\task;

defined('MOODLE_INTERNAL') || die();

class check_expired_matches extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_check_expired', 'mod_knowledgebattle');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $now = time();
        // Find matches where player 1 finished (status = 2) but it has expired
        $sql = "SELECT m.*, kb.win_points, kb.loss_points, kb.allow_negative_points, kb.coursemodule 
                FROM {knowledgebattle_matches} m
                JOIN {knowledgebattle} kb ON m.knowledgebattleid = kb.id
                WHERE m.status = 2 AND m.timeexpire < :now";
        
        $matches = $DB->get_records_sql($sql, ['now' => $now]);

        if (empty($matches)) {
            return;
        }

        foreach ($matches as $match) {
            // Set match as expired WO
            $match->status = 4; // W.O.
            $match->winner_id = $match->player1_id;
            $match->timecompleted = $now;

            $DB->update_record('knowledgebattle_matches', $match);

            // Trigger battle_expired_wo event
            if (class_exists('\mod_knowledgebattle\event\battle_expired_wo')) {
                $context = \context_module::instance($match->coursemodule);
                $event = \mod_knowledgebattle\event\battle_expired_wo::create([
                    'objectid' => $match->id,
                    'context' => $context,
                    'relateduserid' => $match->player2_id,
                ]);
                $event->trigger();
            }

            // Update Player 1 stats (Winner)
            $this->update_user_stats($match->knowledgebattleid, $match->player1_id, $match->win_points, true);

            // Update Player 2 stats (Loser) if player 2 exists
            if (!empty($match->player2_id)) {
                $this->update_user_stats($match->knowledgebattleid, $match->player2_id, $match->loss_points, false, $match->allow_negative_points);
            }

            // In a real implementation, notification sending code would go here
            // using message_send() to both players about the W.O. result.
        }
    }

    /**
     * Helper to update user stats.
     */
    private function update_user_stats($kbid, $userid, $points, $is_win, $allow_negative = 0) {
        global $DB;

        $stats = $DB->get_record('knowledgebattle_user_stats', ['knowledgebattleid' => $kbid, 'userid' => $userid]);
        
        if (!$stats) {
            $stats = new \stdClass();
            $stats->knowledgebattleid = $kbid;
            $stats->userid = $userid;
            $stats->matches_played = 0;
            $stats->wins = 0;
            $stats->losses = 0;
            $stats->draws = 0;
            $stats->current_points = 0;
            $stats->streak = 0;
            $stats->id = $DB->insert_record('knowledgebattle_user_stats', $stats);
        }

        $stats->matches_played++;
        
        if ($is_win) {
            $stats->wins++;
            $stats->streak++;
        } else {
            $stats->losses++;
            $stats->streak = 0; // Reset streak on loss
        }

        $stats->current_points += $points;
        if (!$allow_negative && $stats->current_points < 0) {
            $stats->current_points = 0;
        }

        $DB->update_record('knowledgebattle_user_stats', $stats);
    }
}
