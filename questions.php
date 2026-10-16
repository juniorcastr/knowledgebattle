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
 * Manage questions for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('knowledgebattle', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/knowledgebattle:managequestions', $context);

$PAGE->set_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($knowledgebattle->name) . ' - ' . get_string('manage_questions_tab', 'mod_knowledgebattle'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_questions_tab', 'mod_knowledgebattle'));

// Fetch question pool records.
$questions_records = $DB->get_records('knowledgebattle_questions', ['battleid' => $knowledgebattle->id], 'id DESC');
$approved = 0;
$pending = 0;
$discarded = 0;
$questions_list = [];

foreach ($questions_records as $q) {
    if ($q->status == 1) {
        $approved++;
    } else if ($q->status == 0) {
        $pending++;
    } else if ($q->status == 2) {
        $discarded++;
    }

    $opts = json_decode($q->options_json, true);
    if (!is_array($opts)) {
        $opts = [];
    }

    $questions_list[] = [
        'id' => (int)$q->id,
        'question_text' => $q->question_text,
        'explanation' => (string)($q->explanation ?? ''),
        'difficulty' => $q->difficulty ?? 'medium',
        'options_json' => $q->options_json,
        'option_0' => (string)($opts[0] ?? ''),
        'option_1' => (string)($opts[1] ?? ''),
        'option_2' => (string)($opts[2] ?? ''),
        'option_3' => (string)($opts[3] ?? ''),
        'correct_index' => (int)$q->correct_index,
        'is_approved' => ($q->status == 1),
        'is_pending' => ($q->status == 0),
        'is_discarded' => ($q->status == 2),
    ];
}

$templatecontext = [
    'cmid' => (int)$cm->id,
    'battleid' => (int)$knowledgebattle->id,
    'view_url' => (new moodle_url('/mod/knowledgebattle/view.php', ['id' => $cm->id]))->out(false),
    'questions' => $questions_list,
    'stats' => [
        'approved' => $approved,
        'pending' => $pending,
        'discarded' => $discarded
    ]
];

echo $OUTPUT->render_from_template('mod_knowledgebattle/question_manager', $templatecontext);

$PAGE->requires->js_call_amd('mod_knowledgebattle/question_manager', 'init', [[
    'cmid' => (int)$cm->id,
    'battleid' => (int)$knowledgebattle->id,
    'view_url' => (new moodle_url('/mod/knowledgebattle/view.php', ['id' => $cm->id]))->out(false),
]]);

echo $OUTPUT->footer();
