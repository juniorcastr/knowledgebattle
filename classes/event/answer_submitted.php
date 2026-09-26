<?php
/**
 * Answer submitted event.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\event;

defined('MOODLE_INTERNAL') || die();

class answer_submitted extends \core\event\base {
    protected function init() {
        $this->data['objecttable'] = 'knowledgebattle_turns';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('event_answer_submitted', 'mod_knowledgebattle');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' submitted an answer in the knowledgebattle activity with course module id '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $this->contextinstanceid]);
    }

    public static function get_objectid_mapping() {
        return ['db' => 'knowledgebattle_turns', 'restore' => 'knowledgebattle_turn'];
    }
}
