<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Backup task class.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
