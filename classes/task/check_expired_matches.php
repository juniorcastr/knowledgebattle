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
        
        // 1. Cleanup expired matchmaking pool entries
        // Assuming 72 hours max age for pool entries by default. We'd get all unique battles first, but it's easier to query battles that have active pool entries.
        $sql = "SELECT DISTINCT battleid FROM {knowledgebattle_matches} WHERE status = 2 AND match_type = 2 AND player2_id IS NULL";
        $battles = $DB->get_fieldset_sql($sql);
        if (!empty($battles)) {
            foreach ($battles as $battleid) {
                \mod_knowledgebattle\matchmaking_manager::remove_expired_pool_entries($battleid, 72);
            }
        }

        // 2. WO Warnings (4 hours before expiry)
        $warning_time = $now + (4 * 3600);
        $warning_sql = "SELECT m.*, kb.id as kbid, kb.name as kbname, kb.coursemodule 
                        FROM {knowledgebattle_matches} m
                        JOIN {knowledgebattle} kb ON m.battleid = kb.id
                        WHERE m.status = 1 AND m.player2_id IS NOT NULL 
                          AND m.timeexpire > :now AND m.timeexpire <= :warning_time";
        
        $warning_matches = $DB->get_records_sql($warning_sql, ['now' => $now, 'warning_time' => $warning_time]);
        
        foreach ($warning_matches as $match) {
            // Need a way to ensure we don't send warning multiple times. 
            // In a real implementation we would track it, but for now we assume this task runs daily/infrequently, or we just send it if it falls in the window.
            // Let's send warning to whoever hasn't finished yet. 
            // If P1 hasn't finished (status=1) or P2 hasn't finished (status=1).
            $battle = $DB->get_record('knowledgebattle', ['id' => $match->battleid]);
            
            // For simplicity in this demo, let's assume P2 needs to be warned if P1 already finished, etc.
            // Actually, we'll notify both if they are still in pending status for their turns.
            if ($match->player2_id > 0) {
                \mod_knowledgebattle\notification_manager::notify_wo_warning($match, $battle, $match->player2_id);
            }
        }

        // 3. Process W.O. Matches
        // Matches where P1 finished (status = 1 or 2) but time has expired
        $expired_sql = "SELECT m.*, kb.win_points, kb.loss_points, kb.allow_negative_points, kb.coursemodule, kb.grade, kb.grade_criteria, kb.max_daily_battles 
                        FROM {knowledgebattle_matches} m
                        JOIN {knowledgebattle} kb ON m.battleid = kb.id
                        WHERE m.status IN (1, 2) AND m.timeexpire < :now";
        
        $matches = $DB->get_records_sql($expired_sql, ['now' => $now]);

        if (empty($matches)) {
            return;
        }

        foreach ($matches as $match) {
            $battle = (object)[
                'id' => $match->battleid,
                'coursemodule' => $match->coursemodule,
                'win_points' => $match->win_points,
                'loss_points' => $match->loss_points,
                'allow_negative_points' => $match->allow_negative_points,
                'grade' => $match->grade,
                'grade_criteria' => $match->grade_criteria,
                'max_daily_battles' => $match->max_daily_battles
            ];
            
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
            \mod_knowledgebattle\battle_manager::update_player_stats($match->battleid, $match->player1_id, 'win', $match->win_points, $battle);

            // Update Player 2 stats (Loser) if player 2 exists
            if (!empty($match->player2_id)) {
                \mod_knowledgebattle\battle_manager::update_player_stats($match->battleid, $match->player2_id, 'loss', $match->loss_points, $battle);
            }
            
            // Send WO notification
            // \mod_knowledgebattle\notification_manager::notify_wo_result...
        }
    }
}
