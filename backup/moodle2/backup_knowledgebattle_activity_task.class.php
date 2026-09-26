<?php
/**
 * Backup task class.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/knowledgebattle/backup/moodle2/backup_knowledgebattle_stepslib.php');

class backup_knowledgebattle_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // No specific settings for this activity.
    }

    protected function define_my_steps() {
        $this->add_step(new backup_knowledgebattle_activity_structure_step('knowledgebattle_structure', 'knowledgebattle.xml'));
    }

    static public function encode_content_links($content) {
        return $content;
    }
}
