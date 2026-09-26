<?php
/**
 * Analytics report and student performance dashboard for teachers.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('knowledgebattle', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/knowledgebattle:viewallstats', $context);

// Handle manual recalculation of grades.
if ($action === 'regrade' && confirm_sesskey()) {
    require_capability('moodle/grade:edit', $context);
    require_once($CFG->dirroot . '/mod/knowledgebattle/lib.php');
    \mod_knowledgebattle\grade_calculator::update_all_grades($knowledgebattle);
    redirect(
        new moodle_url('/mod/knowledgebattle/report.php', ['id' => $cm->id]),
        'Notas recalculadas com sucesso no Livro de Notas!',
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Handle CSV export.
if ($action === 'exportcsv') {
    require_capability('mod/knowledgebattle:viewallstats', $context);

    $sql = "SELECT s.*, u.firstname, u.lastname, u.email
              FROM {knowledgebattle_user_stats} s
              JOIN {user} u ON u.id = s.userid
             WHERE s.battleid = ?
          ORDER BY s.current_points DESC, s.wins DESC";
    $stats = $DB->get_records_sql($sql, [$knowledgebattle->id]);

    $filename = 'knowledgebattle_report_' . $knowledgebattle->id . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Aluno', 'Nome', 'Sobrenome', 'E-mail', 'Pontos', 'Batalhas', 'Vitorias', 'Derrotas', 'Empates', 'Streak Atual', 'Maior Streak', 'Nota Calculada']);

    foreach ($stats as $st) {
        $grade = \mod_knowledgebattle\grade_calculator::calculate_grade($knowledgebattle, $st->userid);
        fputcsv($output, [
            $st->userid,
            $st->firstname,
            $st->lastname,
            $st->email,
            $st->current_points,
            $st->matches_played,
            $st->wins,
            $st->losses,
            $st->draws,
            $st->current_streak,
            $st->max_streak,
            $grade !== null ? $grade : 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

$PAGE->set_url('/mod/knowledgebattle/report.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($knowledgebattle->name) . ' - Relatório Analítico');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/knowledgebattle/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_title', 'mod_knowledgebattle'));

// 1. Overall battle statistics.
$total_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id]);
$completed_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id, 'status' => 3]);
$wo_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id, 'status' => 4]);
$direct_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id, 'match_type' => 1]);
$mm_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id, 'match_type' => 2]);
$bot_matches = $DB->count_records('knowledgebattle_matches', ['battleid' => $knowledgebattle->id, 'is_bot_match' => 1]);

// 2. Question difficulty breakdown (accuracy rate per question).
$sql_questions = "SELECT q.id, q.question_text, q.difficulty,
                         COUNT(t.id) as total_answers,
                         SUM(CASE WHEN t.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
                    FROM {knowledgebattle_questions} q
               LEFT JOIN {knowledgebattle_turns} t ON t.questionid = q.id
                   WHERE q.battleid = ?
                GROUP BY q.id, q.question_text, q.difficulty
                ORDER BY (CASE WHEN COUNT(t.id) > 0 THEN (SUM(CASE WHEN t.is_correct = 1 THEN 1 ELSE 0 END) * 1.0 / COUNT(t.id)) ELSE 1 END) ASC";

$question_analytics = [];
$q_records = $DB->get_records_sql($sql_questions, [$knowledgebattle->id]);
foreach ($q_records as $qr) {
    $total_ans = (int)$qr->total_answers;
    $correct_ans = (int)$qr->correct_answers;
    $accuracy_pct = $total_ans > 0 ? round(($correct_ans / $total_ans) * 100) : 100;

    $is_critical = ($total_ans >= 3 && $accuracy_pct < 50);

    $question_analytics[] = [
        'id' => (int)$qr->id,
        'question_text' => $qr->question_text,
        'difficulty' => $qr->difficulty,
        'total_answers' => $total_ans,
        'correct_answers' => $correct_ans,
        'accuracy_pct' => $accuracy_pct,
        'is_critical' => $is_critical
    ];
}

// 3. Student performance rankings.
$sql_students = "SELECT s.*, u.firstname, u.lastname, u.email
                   FROM {knowledgebattle_user_stats} s
                   JOIN {user} u ON u.id = s.userid
                  WHERE s.battleid = ?
               ORDER BY s.current_points DESC, s.wins DESC";

$students_records = $DB->get_records_sql($sql_students, [$knowledgebattle->id]);
$students_list = [];
$rank = 1;

foreach ($students_records as $st) {
    $grade = \mod_knowledgebattle\grade_calculator::calculate_grade($knowledgebattle, $st->userid);
    $win_rate = $st->matches_played > 0 ? round(($st->wins / $st->matches_played) * 100) : 0;

    $students_list[] = [
        'rank' => $rank++,
        'userid' => (int)$st->userid,
        'fullname' => fullname($st),
        'email' => $st->email,
        'points' => (int)$st->current_points,
        'matches_played' => (int)$st->matches_played,
        'wins' => (int)$st->wins,
        'losses' => (int)$st->losses,
        'draws' => (int)$st->draws,
        'win_rate' => $win_rate . '%',
        'current_streak' => (int)$st->current_streak,
        'max_streak' => (int)$st->max_streak,
        'grade' => $grade !== null ? number_format($grade, 1) : '-'
    ];
}

$templatecontext = [
    'cmid' => (int)$cm->id,
    'battleid' => (int)$knowledgebattle->id,
    'sesskey' => sesskey(),
    'stats_summary' => [
        'total_matches' => $total_matches,
        'completed_matches' => $completed_matches,
        'wo_matches' => $wo_matches,
        'direct_matches' => $direct_matches,
        'mm_matches' => $mm_matches,
        'bot_matches' => $bot_matches,
        'total_students' => count($students_list)
    ],
    'questions_analytics' => $question_analytics,
    'students_list' => $students_list,
    'has_students' => !empty($students_list),
    'has_questions' => !empty($question_analytics)
];

echo html_writer::start_div('mod-knowledgebattle-report');
echo $OUTPUT->render_from_template('mod_knowledgebattle/report', $templatecontext);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(new moodle_url('/mod/knowledgebattle/view.php', ['id' => $cm->id]), '← Voltar para a Atividade', ['class' => 'btn btn-secondary mt-4']),
    'text-center'
);

echo $OUTPUT->footer();
