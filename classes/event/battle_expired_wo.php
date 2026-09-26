<?php
/**
 * Battle expired WO event.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\event;

defined('MOODLE_INTERNAL') || die();

class battle_expired_wo extends \core\event\base {
    protected function init() {
        $this->data['objecttable'] = 'knowledgebattle_matches';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('event_battle_expired', 'mod_knowledgebattle');
    }

    public function get_description() {
        return "The battle with id '{$this->objectid}' expired (W.O.) in the knowledgebattle activity with course module id '{$this->contextinstanceid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/knowledgebattle/view.php', ['id' => $this->contextinstanceid]);
    }

    public static function get_objectid_mapping() {
        return ['db' => 'knowledgebattle_matches', 'restore' => 'knowledgebattle_match'];
    }
}
