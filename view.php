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

echo html_writer::start_div('mod-knowledgebattle', [
    'id' => 'knowledgebattle-content',
    'data-battleid' => (int)$knowledgebattle->id,
    'data-cmid' => (int)$cm->id
]);

if ($action === 'manage' && has_capability('mod/knowledgebattle:managequestions', $context)) {
    redirect(new moodle_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]));
} else {
    $has_manage = has_capability('mod/knowledgebattle:managequestions', $context);
    $has_report = has_capability('mod/knowledgebattle:viewallstats', $context);
    if ($has_manage || $has_report) {
        echo html_writer::start_div('mb-3 d-flex flex-wrap align-items-center');
        if ($has_manage) {
            echo html_writer::link(
                new moodle_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]),
                '<i class="fa fa-question-circle mr-1 me-1"></i> ' . get_string('manage_questions_tab', 'mod_knowledgebattle'),
                ['class' => 'btn btn-outline-secondary mr-2 me-2 mb-2']
            );
        }
        if ($has_report) {
            echo html_writer::link(
                new moodle_url('/mod/knowledgebattle/report.php', ['id' => $cm->id]),
                '<i class="fa fa-bar-chart mr-1 me-1"></i> ' . get_string('report_tab', 'mod_knowledgebattle'),
                ['class' => 'btn btn-outline-info mr-2 me-2 mb-2']
            );
        }
        echo html_writer::tag('button',
            '<i class="fa fa-life-ring mr-1 me-1"></i> ' . get_string('help_guide_btn', 'mod_knowledgebattle'),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-primary mb-2',
                'id' => 'btn-kb-help-guide',
                'data-toggle' => 'modal',
                'data-bs-toggle' => 'modal',
                'data-target' => '#modal-kb-help',
                'data-bs-target' => '#modal-kb-help'
            ]
        );
        echo html_writer::end_div();

        $is_pt = (strpos(current_language(), 'pt') === 0);
        $helpcontext = [
            'is_pt' => $is_pt,
            'cmid' => (int)$cm->id,
            'admin_url' => (new moodle_url('/admin/settings.php', ['section' => 'modsettingknowledgebattle']))->out()
        ];
        echo $OUTPUT->render_from_template('mod_knowledgebattle/help_modal', $helpcontext);
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

    // Check approved questions count.
    $approved_count = $DB->count_records('knowledgebattle_questions', [
        'battleid' => $knowledgebattle->id,
        'status' => 1
    ]);
    $min_questions = (int)($knowledgebattle->questions_per_match ?? 5);
    $has_enough_questions = ($approved_count >= $min_questions);
    $now = time();

    // 1. Incoming challenges for current user (player2_id = current user, not yet completed by user).
    $incoming_sql = "SELECT m.*
                       FROM {knowledgebattle_matches} m
                      WHERE m.battleid = :battleid
                        AND m.player2_id = :userid
                        AND m.status IN (1, 2)
                        AND (m.timeexpire IS NULL OR m.timeexpire > :now)
                   ORDER BY m.timecreated DESC";
    $incoming_records = $DB->get_records_sql($incoming_sql, [
        'battleid' => $knowledgebattle->id,
        'userid' => $USER->id,
        'now' => $now
    ]);

    $incoming_challenges = [];
    foreach ($incoming_records as $m) {
        $user_turns = $DB->count_records('knowledgebattle_turns', [
            'matchid' => $m->id,
            'userid' => $USER->id
        ]);
        if ($user_turns < $min_questions) {
            $p1 = \core_user::get_user($m->player1_id);
            $p1_name = $p1 ? fullname($p1) : get_string('player', 'mod_knowledgebattle');
            $hours_left = !empty($m->timeexpire) ? max(1, (int)ceil(($m->timeexpire - $now) / 3600)) : 24;
            $incoming_challenges[] = [
                'id' => (int)$m->id,
                'challenger_name' => $p1_name,
                'challenger_avatar' => $p1 ? $OUTPUT->user_picture($p1, ['size' => 35]) : '',
                'hours_left' => $hours_left,
                'questions_count' => $min_questions,
            ];
        }
    }

    // 2. Waiting matches (matches sent by current user waiting for opponent to finish).
    $waiting_sql = "SELECT m.*
                      FROM {knowledgebattle_matches} m
                     WHERE m.battleid = :battleid
                       AND m.player1_id = :userid
                       AND m.status IN (1, 2)
                       AND (m.timeexpire IS NULL OR m.timeexpire > :now)
                  ORDER BY m.timecreated DESC";
    $waiting_records = $DB->get_records_sql($waiting_sql, [
        'battleid' => $knowledgebattle->id,
        'userid' => $USER->id,
        'now' => $now
    ]);

    $waiting_matches = [];
    foreach ($waiting_records as $m) {
        $user_turns = $DB->count_records('knowledgebattle_turns', [
            'matchid' => $m->id,
            'userid' => $USER->id
        ]);
        if ($user_turns >= $min_questions) {
            if ($m->match_type == 1 && !empty($m->player2_id)) {
                $p2 = \core_user::get_user($m->player2_id);
                $p2_name = $p2 ? fullname($p2) : get_string('player', 'mod_knowledgebattle');
                $desc = "Desafio direto enviado para {$p2_name}";
            } else {
                $desc = "Batalha Rápida (Fila aberta)";
            }
            $hours_left = !empty($m->timeexpire) ? max(1, (int)ceil(($m->timeexpire - $now) / 3600)) : 24;
            $waiting_matches[] = [
                'id' => (int)$m->id,
                'description' => $desc,
                'hours_left' => $hours_left,
                'p1_score' => (int)$m->p1_score
            ];
        }
    }

    // 3. Recent completed matches for current user.
    $recent_sql = "SELECT m.*
                     FROM {knowledgebattle_matches} m
                    WHERE m.battleid = :battleid
                      AND (m.player1_id = :uid1 OR m.player2_id = :uid2)
                      AND m.status = 3
                 ORDER BY m.timecompleted DESC";
    $recent_records = $DB->get_records_sql($recent_sql, [
        'battleid' => $knowledgebattle->id,
        'uid1' => $USER->id,
        'uid2' => $USER->id
    ], 0, 5);

    $recent_matches = [];
    foreach ($recent_records as $m) {
        if (!empty($m->is_bot_match) || empty($m->player2_id)) {
            $opp_name = get_string('bot_name', 'mod_knowledgebattle');
        } else if ($m->player1_id == $USER->id) {
            $p2 = \core_user::get_user($m->player2_id);
            $opp_name = $p2 ? fullname($p2) : get_string('player', 'mod_knowledgebattle');
        } else {
            $p1 = \core_user::get_user($m->player1_id);
            $opp_name = $p1 ? fullname($p1) : get_string('player', 'mod_knowledgebattle');
        }

        if ($m->winner_id == 0) {
            $label = 'Empate';
            $badge_class = 'badge-warning';
        } else if ($m->winner_id == $USER->id) {
            $label = 'Vitória';
            $badge_class = 'badge-success';
        } else {
            $label = 'Derrota';
            $badge_class = 'badge-danger';
        }

        $my_score = ($m->player1_id == $USER->id) ? (int)$m->p1_score : (int)$m->p2_score;
        $opp_score = ($m->player1_id == $USER->id) ? (int)$m->p2_score : (int)$m->p1_score;
        $score_text = "{$my_score} x {$opp_score}";
        $date = userdate($m->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));

        $recent_matches[] = [
            'id' => (int)$m->id,
            'opponent_name' => $opp_name,
            'result_label' => $label,
            'badge_class' => $badge_class,
            'score_text' => $score_text,
            'date' => $date
        ];
    }

    // 4. Check for an active unfinished match for current user (to resume).
    $active_match = null;
    $in_progress_matches = $DB->get_records_sql(
        "SELECT m.* FROM {knowledgebattle_matches} m
          WHERE m.battleid = ?
            AND (m.player1_id = ? OR m.player2_id = ?)
            AND m.status = 1
            AND (m.timeexpire IS NULL OR m.timeexpire > ?)
       ORDER BY m.timemodified DESC",
        [$knowledgebattle->id, $USER->id, $USER->id, $now]
    );
    foreach ($in_progress_matches as $ipm) {
        $u_turns = $DB->count_records('knowledgebattle_turns', ['matchid' => $ipm->id, 'userid' => $USER->id]);
        if ($u_turns < $min_questions) {
            $active_match = $ipm;
            break;
        }
    }

    $templatecontext = [
        'cmid' => (int)$cm->id,
        'battleid' => (int)$knowledgebattle->id,
        'bot_enabled' => (bool)$knowledgebattle->bot_enabled,
        'enrolled_users' => $enrolled_users,
        'user_stats' => $stats_data,
        'user_rank' => $user_rank,
        'active_matchid' => $active_match ? (int)$active_match->id : 0,
        'approved_count' => (int)$approved_count,
        'min_questions' => (int)$min_questions,
        'has_enough_questions' => (bool)$has_enough_questions,
        'has_manage' => (bool)$has_manage,
        'incoming_challenges' => $incoming_challenges,
        'has_incoming_challenges' => !empty($incoming_challenges),
        'incoming_count' => count($incoming_challenges),
        'waiting_matches' => $waiting_matches,
        'has_waiting_matches' => !empty($waiting_matches),
        'recent_matches' => $recent_matches,
        'has_recent_matches' => !empty($recent_matches),
    ];

    echo $OUTPUT->render_from_template('mod_knowledgebattle/lobby', $templatecontext);

    $PAGE->requires->js_call_amd('mod_knowledgebattle/battle', 'init', [[
        'cmid' => (int)$cm->id,
        'battleid' => (int)$knowledgebattle->id,
        'currentUserId' => (int)$USER->id,
        'timePerQuestion' => (int)($knowledgebattle->time_per_question ?? 30),
        'activeMatchId' => $active_match ? (int)$active_match->id : 0
    ]]);
}

echo html_writer::end_div();
echo $OUTPUT->footer();
