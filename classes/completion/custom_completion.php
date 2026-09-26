<?php
/**
 * Activity custom completion implementation for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\completion;

use core_completion\activity_custom_completion;

defined('MOODLE_INTERNAL') || die();

class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $stats = $DB->get_record('knowledgebattle_user_stats', [
            'battleid' => $this->cm->instance,
            'userid' => $this->userid
        ]);

        if (!$stats) {
            return COMPLETION_INCOMPLETE;
        }

        switch ($rule) {
            case 'completionbattles':
                $min_battles = $this->cm->customdata['customcompletionrules']['completionbattles'] ?? 1;
                return ($stats->matches_played >= $min_battles) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            case 'completionwins':
                $min_wins = $this->cm->customdata['customcompletionrules']['completionwins'] ?? 1;
                return ($stats->wins >= $min_wins) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

            default:
                return COMPLETION_INCOMPLETE;
        }
    }

    /**
     * Fetch the list of custom completion rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionbattles',
            'completionwins'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $min_battles = $this->cm->customdata['customcompletionrules']['completionbattles'] ?? 1;
        $min_wins = $this->cm->customdata['customcompletionrules']['completionwins'] ?? 1;

        return [
            'completionbattles' => get_string('completionbattles_desc', 'mod_knowledgebattle', $min_battles),
            'completionwins' => get_string('completionwins_desc', 'mod_knowledgebattle', $min_wins),
        ];
    }

    /**
     * Returns an array of rule names in sort order.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionbattles',
            'completionwins'
        ];
    }
}
