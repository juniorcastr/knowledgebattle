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
 * Upgrade code for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_knowledgebattle upgrade tasks between versions.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always returns true on success.
 */
function xmldb_knowledgebattle_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2026101600) {
        // The cache TTL setting was never used by the plugin and has been removed.
        unset_config('global_question_cache_ttl', 'mod_knowledgebattle');
        upgrade_mod_savepoint(true, 2026101600, 'knowledgebattle');
    }

    return true;
}
