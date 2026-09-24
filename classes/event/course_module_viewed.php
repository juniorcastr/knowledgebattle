<?php
/**
 * Event for course module viewed.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialize the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'knowledgebattle';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_objectid_mapping() {
        return ['db' => 'knowledgebattle', 'restore' => 'knowledgebattle'];
    }
}
