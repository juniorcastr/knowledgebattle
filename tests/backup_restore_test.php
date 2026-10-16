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
 * Backup and restore tests for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Backup and restore tests.
 *
 * @covers \backup_knowledgebattle_activity_structure_step
 * @covers \restore_knowledgebattle_activity_structure_step
 */
final class backup_restore_test extends \advanced_testcase {

    /**
     * Backs up a course and restores it into a new one.
     *
     * @param \stdClass $course
     * @param bool $userdata
     * @return \stdClass The new course.
     */
    private function copy_course(\stdClass $course, bool $userdata): \stdClass {
        global $CFG, $USER;

        $this->setAdminUser();
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        $bc = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id);
        $bc->get_plan()->get_setting('users')->set_value($userdata);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourse = $this->getDataGenerator()->create_course();
        $rc = new \restore_controller($backupid, $newcourse->id, \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL, $USER->id, \backup::TARGET_NEW_COURSE);
        $rc->get_plan()->get_setting('users')->set_value($userdata);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourse;
    }

    /**
     * A course with battles, questions, turns and stats survives backup and restore.
     */
    public function test_backup_restore_with_userdata(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_knowledgebattle');
        $battle = $gen->create_instance(['course' => $course->id, 'intro' => 'Intro text']);
        $p1 = $this->getDataGenerator()->create_user();
        $p2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($p1->id, $course->id);
        $this->getDataGenerator()->enrol_user($p2->id, $course->id);

        $q1 = $gen->create_question($battle->id);
        $q2 = $gen->create_question($battle->id, ['question_text' => 'Second?']);

        $now = time();
        $matchid = $DB->insert_record('knowledgebattle_matches', (object) [
            'battleid' => $battle->id, 'player1_id' => $p1->id, 'player2_id' => $p2->id,
            'match_type' => 1, 'is_bot_match' => 0, 'status' => 3, 'winner_id' => $p1->id,
            'p1_score' => 2, 'p2_score' => 1, 'p1_time_ms' => 1000, 'p2_time_ms' => 2000,
            'timestarted' => $now, 'timecompleted' => $now, 'timeexpire' => $now + 3600,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('knowledgebattle_match_questions',
            (object) ['matchid' => $matchid, 'questionid' => $q1->id, 'question_order' => 1]);
        $DB->insert_record('knowledgebattle_match_questions',
            (object) ['matchid' => $matchid, 'questionid' => $q2->id, 'question_order' => 2]);
        $DB->insert_record('knowledgebattle_turns', (object) [
            'matchid' => $matchid, 'userid' => $p1->id, 'questionid' => $q1->id,
            'selected_option' => 1, 'is_correct' => 1, 'response_time_ms' => 500, 'timecreated' => $now,
        ]);
        $DB->insert_record('knowledgebattle_user_stats', (object) [
            'battleid' => $battle->id, 'userid' => $p1->id, 'current_points' => 100,
            'matches_played' => 1, 'wins' => 1, 'losses' => 0, 'draws' => 0,
            'current_streak' => 1, 'max_streak' => 1, 'last_battle_date' => $now,
            'daily_battles_count' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);

        $newcourse = $this->copy_course($course, true);

        $newbattle = $DB->get_record('knowledgebattle', ['course' => $newcourse->id], '*', MUST_EXIST);
        $this->assertEquals($battle->name, $newbattle->name);
        $this->assertEquals(2, $DB->count_records('knowledgebattle_questions', ['battleid' => $newbattle->id]));

        $newmatch = $DB->get_record('knowledgebattle_matches', ['battleid' => $newbattle->id], '*', MUST_EXIST);
        $this->assertEquals($p1->id, $newmatch->player1_id);
        $this->assertEquals($p2->id, $newmatch->player2_id);
        $this->assertEquals(2, $newmatch->p1_score);
        $this->assertEquals(2, $DB->count_records('knowledgebattle_match_questions', ['matchid' => $newmatch->id]));
        $this->assertEquals(1, $DB->count_records('knowledgebattle_turns', ['matchid' => $newmatch->id]));

        $stats = $DB->get_record('knowledgebattle_user_stats',
            ['battleid' => $newbattle->id, 'userid' => $p1->id], '*', MUST_EXIST);
        $this->assertEquals(100, $stats->current_points);
        $this->assertEquals(1, $stats->max_streak);

        // The restored turn must point to a restored question, not to the original one.
        $turn = $DB->get_record('knowledgebattle_turns', ['matchid' => $newmatch->id], '*', MUST_EXIST);
        $this->assertNotEquals($q1->id, $turn->questionid);
        $this->assertTrue($DB->record_exists('knowledgebattle_questions',
            ['id' => $turn->questionid, 'battleid' => $newbattle->id]));
    }

    /**
     * Without user data only the activity and its questions are restored.
     */
    public function test_backup_restore_without_userdata(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_knowledgebattle');
        $battle = $gen->create_instance(['course' => $course->id]);
        $gen->create_question($battle->id);

        $newcourse = $this->copy_course($course, false);

        $newbattle = $DB->get_record('knowledgebattle', ['course' => $newcourse->id], '*', MUST_EXIST);
        $this->assertEquals(1, $DB->count_records('knowledgebattle_questions', ['battleid' => $newbattle->id]));
        $this->assertEquals(0, $DB->count_records('knowledgebattle_matches', ['battleid' => $newbattle->id]));
        $this->assertEquals(0, $DB->count_records('knowledgebattle_user_stats', ['battleid' => $newbattle->id]));
    }
}
