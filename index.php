<?php
/**
 * Index knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
