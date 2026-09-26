<?php
/**
 * Upgrade code for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

    return true;
}
