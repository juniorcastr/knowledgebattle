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
 * Web services definition.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        'capabilities' => 'mod/knowledgebattle:view',
        'services' => [],
    ],
    'mod_knowledgebattle_submit_answer' => [
        'classname' => 'mod_knowledgebattle\external\submit_answer',
        'methodname' => 'execute',
        'description' => 'Submits an answer for the current question',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:view',
        'services' => [],
    ],
    'mod_knowledgebattle_get_battle_result' => [
        'classname' => 'mod_knowledgebattle\external\get_battle_result',
        'methodname' => 'execute',
        'description' => 'Gets the final result of a completed battle',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:view',
        'services' => [],
    ],
    'mod_knowledgebattle_get_leaderboard' => [
        'classname' => 'mod_knowledgebattle\external\get_leaderboard',
        'methodname' => 'execute',
        'description' => 'Gets the leaderboard for a knowledgebattle instance',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:view',
        'services' => [],
    ],
    'mod_knowledgebattle_get_user_stats' => [
        'classname' => 'mod_knowledgebattle\external\get_user_stats',
        'methodname' => 'execute',
        'description' => 'Gets the statistics for the current user',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/knowledgebattle:view',
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
