<?php
/**
 * View knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$kbid  = optional_param('k', 0, PARAM_INT);  // Knowledge Battle instance ID.

if ($id) {
    $cm         = get_coursemodule_from_id('knowledgebattle', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $knowledgebattle  = $DB->get_record('knowledgebattle', ['id' => $cm->instance], '*', MUST_EXIST);
} elseif ($kbid) {
    $knowledgebattle  = $DB->get_record('knowledgebattle', ['id' => $kbid], '*', MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $knowledgebattle->course], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('knowledgebattle', $knowledgebattle->id, $course->id, false, MUST_EXIST);
} else {
    print_error('invalidcoursemodule');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/knowledgebattle:view', $context);

$PAGE->set_url('/mod/knowledgebattle/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($knowledgebattle->name));
$PAGE->set_heading(format_string($course->fullname));

// Trigger viewed event.
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

if (has_capability('mod/knowledgebattle:managequestions', $context)) {
    echo $OUTPUT->box('Módulo Professor: <a href="questions.php?id='.$cm->id.'">Gerenciar Questões</a>', 'generalbox');
} else {
    echo $OUTPUT->box('Bem-vindo à Batalha! Escolha seu oponente...', 'generalbox');
    echo '<button class="btn btn-primary">Iniciar Partida Aleatória</button> ';
    if ($knowledgebattle->bot_enabled) {
        echo '<button class="btn btn-secondary">Desafiar o Bot</button>';
    }
}

echo $OUTPUT->footer();
