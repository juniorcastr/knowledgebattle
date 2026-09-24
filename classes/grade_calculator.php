<?php
/**
 * Grade Calculator for knowledgebattle
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

class grade_calculator {

    /**
     * Calculates grade based on criteria.
     *
     * @param object $battle
     * @param int $userid
     * @return float|null
     */
    public static function calculate_grade(object $battle, int $userid): ?float {
        global $DB;

        if ($battle->grade == 0) return null;

        $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $battle->id, 'userid' => $userid]);
        if (!$stats) return null;

        $max_grade = $battle->grade > 0 ? $battle->grade : 100; // Simplified scale handling
        $criteria = $battle->grade_criteria ?? 1;
        $calculated = 0.0;

        switch ($criteria) {
            case 1: // Points
                // Example logic: Win points * max daily * 5 days as a max cap
                $win_pts = $battle->win_points ?? 3;
                $daily = $battle->max_daily_battles ?? 5;
                $max_possible_points = $win_pts * $daily * 5; 
                if ($max_possible_points <= 0) $max_possible_points = 50;
                
                $ratio = $stats->current_points / $max_possible_points;
                $calculated = $ratio * $max_grade;
                break;
                
            case 2: // Wins
                $expected_wins = 10; // Configurable in future
                $ratio = $stats->wins / $expected_wins;
                $calculated = $ratio * $max_grade;
                break;
                
            case 3: // Participation
                $daily = $battle->max_daily_battles ?? 5;
                $expected_matches = $daily * 5;
                $ratio = $stats->matches_played / $expected_matches;
                $calculated = $ratio * $max_grade;
                break;
        }

        if ($calculated < 0) $calculated = 0;
        if ($calculated > $max_grade) $calculated = $max_grade;

        return round($calculated, 5);
    }

    /**
     * Updates all grades for a battle.
     *
     * @param object $battle
     */
    public static function update_all_grades(object $battle): void {
        global $DB;
        
        $stats = $DB->get_records('knowledgebattle_user_stats', ['battleid' => $battle->id]);
        
        foreach ($stats as $stat) {
            $gradeval = self::calculate_grade($battle, $stat->userid);
            if ($gradeval !== null) {
                $grade = new \stdClass();
                $grade->userid = $stat->userid;
                $grade->rawgrade = $gradeval;
                knowledgebattle_grade_item_update($battle, $grade);
            }
        }
    }
}
