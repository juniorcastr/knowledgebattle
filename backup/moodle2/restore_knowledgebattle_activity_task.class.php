<?php
/**
 * Restore task class.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/knowledgebattle/backup/moodle2/restore_knowledgebattle_stepslib.php');

class restore_knowledgebattle_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // No specific settings
    }

    protected function define_my_steps() {
        $this->add_step(new restore_knowledgebattle_activity_structure_step('knowledgebattle_structure', 'knowledgebattle.xml'));
    }

    static public function define_decode_contents() {
        return [];
    }

    static public function define_decode_rules() {
        return [];
    }
}
