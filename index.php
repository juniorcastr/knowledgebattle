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
 * Index knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);
$PAGE->set_pagelayout('incourse');

$PAGE->set_url('/mod/knowledgebattle/index.php', ['id' => $id]);
$PAGE->set_title($course->shortname . ': ' . get_string('modulenameplural', 'mod_knowledgebattle'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_knowledgebattle'));

$knowledgebattles = get_all_instances_in_course('knowledgebattle', $course);

if (empty($knowledgebattles)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_knowledgebattle')), new moodle_url('/course/view.php', ['id' => $course->id]));
    die;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$table->head  = [
    get_string('name'),
    get_string('description'),
    get_string('pool_size', 'mod_knowledgebattle')
];
$table->align = ['left', 'left', 'center'];

foreach ($knowledgebattles as $kb) {
    if (!$kb->visible) {
        $link = html_writer::link(new moodle_url('/mod/knowledgebattle/view.php', ['id' => $kb->coursemodule]), format_string($kb->name), ['class' => 'dimmed']);
    } else {
        $link = html_writer::link(new moodle_url('/mod/knowledgebattle/view.php', ['id' => $kb->coursemodule]), format_string($kb->name));
    }
    
    $table->data[] = [
        $link,
        format_text($kb->intro, $kb->introformat),
        $kb->pool_size
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
