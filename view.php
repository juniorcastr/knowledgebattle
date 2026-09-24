<?php
/**
 * View knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$kbid  = optional_param('k', 0, PARAM_INT);
$action = optional_param('action', 'lobby', PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('knowledgebattle', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $cm->instance], '*', MUST_EXIST);
} elseif ($kbid) {
    $knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $kbid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $knowledgebattle->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('knowledgebattle', $knowledgebattle->id, $course->id, false, MUST_EXIST);
} else {
    print_error('invalidcoursemodule');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/knowledgebattle:view', $context);

$PAGE->set_url('/mod/knowledgebattle/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($knowledgebattle->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/knowledgebattle/styles.css');

$event = \mod_knowledgebattle\event\course_module_viewed::create([
    'objectid' => $knowledgebattle->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('knowledgebattle', $knowledgebattle);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($knowledgebattle->name));

if (!empty($knowledgebattle->intro)) {
    echo $OUTPUT->box(format_module_intro('knowledgebattle', $knowledgebattle, $cm->id), 'generalbox', 'intro');
}

echo html_writer::start_div('mod-knowledgebattle', ['id' => 'knowledgebattle-content']);

if ($action === 'manage' && has_capability('mod/knowledgebattle:managequestions', $context)) {
    redirect(new moodle_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]));
} else {
    $has_manage = has_capability('mod/knowledgebattle:managequestions', $context);
    $has_report = has_capability('mod/knowledgebattle:viewallstats', $context);
    if ($has_manage || $has_report) {
        echo html_writer::start_div('mb-3');
        if ($has_manage) {
            echo html_writer::link(
                new moodle_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]),
                '<i class="fa fa-question-circle"></i> ' . get_string('manage_questions_tab', 'mod_knowledgebattle'),
                ['class' => 'btn btn-outline-secondary mr-2']
            );
        }
        if ($has_report) {
            echo html_writer::link(
                new moodle_url('/mod/knowledgebattle/report.php', ['id' => $cm->id]),
                '<i class="fa fa-bar-chart"></i> ' . get_string('report_tab', 'mod_knowledgebattle'),
                ['class' => 'btn btn-outline-info']
            );
        }
        echo html_writer::end_div();
    }

    // Fetch user stats.
    $userstats = $DB->get_record('knowledgebattle_user_stats', [
        'battleid' => $knowledgebattle->id,
        'userid' => $USER->id
    ]);

    $stats_data = [
        'current_points' => $userstats ? (int)$userstats->current_points : 0,
        'wins' => $userstats ? (int)$userstats->wins : 0,
        'current_streak' => $userstats ? (int)$userstats->current_streak : 0
    ];

    $user_rank = \mod_knowledgebattle\battle_manager::get_user_rank($knowledgebattle->id, $USER->id);

    // Fetch enrolled peers for direct challenges.
    $enrolled_users = [];
    $course_context = context_course::instance($course->id);
    $users = get_enrolled_users($course_context, 'mod/knowledgebattle:view', 0, 'u.id, u.firstname, u.lastname', 'u.firstname ASC', 0, 100);
    foreach ($users as $u) {
        if ($u->id != $USER->id) {
            $enrolled_users[] = [
                'id' => (int)$u->id,
                'fullname' => fullname($u)
            ];
        }
    }

    // Check for an active unfinished match for current user.
    $active_match = $DB->get_record_sql(
        "SELECT id FROM {knowledgebattle_matches}
          WHERE battleid = ?
            AND (player1_id = ? OR player2_id = ?)
            AND status = 1",
        [$knowledgebattle->id, $USER->id, $USER->id],
        IGNORE_MULTIPLE
    );

    $templatecontext = [
        'cmid' => $cm->id,
        'battleid' => $knowledgebattle->id,
        'bot_enabled' => (bool)$knowledgebattle->bot_enabled,
        'enrolled_users' => $enrolled_users,
        'user_stats' => $stats_data,
        'user_rank' => $user_rank,
        'active_matchid' => $active_match ? (int)$active_match->id : 0
    ];

    echo $OUTPUT->render_from_template('mod_knowledgebattle/lobby', $templatecontext);

    $PAGE->requires->js_call_amd('mod_knowledgebattle/battle', 'init', [
        'cmid' => (int)$cm->id,
        'battleid' => (int)$knowledgebattle->id,
        'currentUserId' => (int)$USER->id,
        'timePerQuestion' => (int)($knowledgebattle->time_per_question ?? 30),
        'activeMatchId' => $active_match ? (int)$active_match->id : 0
    ]);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
