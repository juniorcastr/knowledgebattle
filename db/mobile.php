<?php
/**
 * Mobile configuration for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_knowledgebattle' => [
        'handlers' => [
            'knowledgebattle' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/knowledgebattle/pix/icon.svg',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view',
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_knowledgebattle'],
            ['start_battle', 'mod_knowledgebattle'],
            ['quick_match', 'mod_knowledgebattle'],
            ['challenge_bot', 'mod_knowledgebattle'],
            ['challenge_player', 'mod_knowledgebattle'],
            ['leaderboard', 'mod_knowledgebattle'],
            ['my_stats', 'mod_knowledgebattle'],
            ['points_label', 'mod_knowledgebattle'],
            ['streak_label', 'mod_knowledgebattle'],
        ],
    ],
];
