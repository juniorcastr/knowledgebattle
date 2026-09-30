<?php
/**
 * Web services definition.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_knowledgebattle_start_battle' => [
        'classname' => 'mod_knowledgebattle\external\start_battle',
        'methodname' => 'execute',
        'description' => 'Starts a new battle or joins an existing one',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:view',
        'services' => [],
    ],
    'mod_knowledgebattle_get_question' => [
        'classname' => 'mod_knowledgebattle\external\get_question',
        'methodname' => 'execute',
        'description' => 'Gets the next question for a battle',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'mod_knowledgebattle_submit_answer' => [
        'classname' => 'mod_knowledgebattle\external\submit_answer',
        'methodname' => 'execute',
        'description' => 'Submits an answer for the current question',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
    'mod_knowledgebattle_get_battle_result' => [
        'classname' => 'mod_knowledgebattle\external\get_battle_result',
        'methodname' => 'execute',
        'description' => 'Gets the final result of a completed battle',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'mod_knowledgebattle_get_leaderboard' => [
        'classname' => 'mod_knowledgebattle\external\get_leaderboard',
        'methodname' => 'execute',
        'description' => 'Gets the leaderboard for a knowledgebattle instance',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'mod_knowledgebattle_get_user_stats' => [
        'classname' => 'mod_knowledgebattle\external\get_user_stats',
        'methodname' => 'execute',
        'description' => 'Gets the statistics for the current user',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'mod_knowledgebattle_generate_questions' => [
        'classname' => 'mod_knowledgebattle\external\generate_questions',
        'methodname' => 'execute',
        'description' => 'Generates AI questions for a knowledgebattle instance',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:generatequestions',
        'services' => [],
    ],
    'mod_knowledgebattle_manage_question' => [
        'classname' => 'mod_knowledgebattle\external\manage_question',
        'methodname' => 'execute',
        'description' => 'Approves, discards or edits a generated question',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:managequestions',
        'services' => [],
    ],
    'mod_knowledgebattle_test_ai_connection' => [
        'classname' => 'mod_knowledgebattle\external\test_ai_connection',
        'methodname' => 'execute',
        'description' => 'Tests connection to the configured AI provider and model',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/site:config',
        'services' => [],
    ],
];
