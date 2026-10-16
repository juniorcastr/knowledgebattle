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
 * Security tests for the web services of mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use mod_knowledgebattle\external\generate_questions;
use mod_knowledgebattle\external\get_battle_result;
use mod_knowledgebattle\external\start_battle;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests capability checks, match ownership and parameter limits.
 *
 * @covers \mod_knowledgebattle\external\get_battle_result
 * @covers \mod_knowledgebattle\external\start_battle
 * @covers \mod_knowledgebattle\external\generate_questions
 */
final class external_security_test extends \advanced_testcase {

    /**
     * Creates a course, an activity, a match between two students and a third student.
     *
     * @param int $status Match status.
     * @return array
     */
    private function setup_match(int $status = 1): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_knowledgebattle');
        $battle = $gen->create_instance(['course' => $course->id]);
        $q = $gen->create_question($battle->id);

        $p1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $p2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $now = time();
        $matchid = $DB->insert_record('knowledgebattle_matches', (object) [
            'battleid' => $battle->id, 'player1_id' => $p1->id, 'player2_id' => $p2->id,
            'match_type' => 1, 'is_bot_match' => 0, 'status' => $status, 'winner_id' => 0,
            'p1_score' => 0, 'p2_score' => 0, 'p1_time_ms' => 0, 'p2_time_ms' => 0,
            'timestarted' => $now, 'timecompleted' => 0, 'timeexpire' => $now + 3600,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('knowledgebattle_match_questions',
            (object) ['matchid' => $matchid, 'questionid' => $q->id, 'question_order' => 1]);

        return [$course, $battle, $matchid, $p1, $p2, $other];
    }

    /**
     * A user who is not part of the match cannot read its result.
     */
    public function test_get_battle_result_rejects_non_players(): void {
        $this->resetAfterTest();
        [, , $matchid, , , $other] = $this->setup_match(3);

        $this->setUser($other);
        $this->expectException(\required_capability_exception::class);
        get_battle_result::execute($matchid);
    }

    /**
     * Correct answers are hidden while the match is still running.
     */
    public function test_get_battle_result_hides_answers_until_complete(): void {
        $this->resetAfterTest();
        [, , $matchid, $p1] = $this->setup_match(1);

        $this->setUser($p1);
        $result = get_battle_result::execute($matchid);
        $this->assertFalse($result['is_completed']);
        $this->assertSame('', $result['questions'][0]['correct_answer']);
        $this->assertSame('', $result['questions'][0]['explanation']);
    }

    /**
     * Players can read the answers of a completed match.
     */
    public function test_get_battle_result_reveals_answers_when_complete(): void {
        $this->resetAfterTest();
        [, , $matchid, $p1] = $this->setup_match(3);

        $this->setUser($p1);
        $result = get_battle_result::execute($matchid);
        $this->assertTrue($result['is_completed']);
        $this->assertSame('4', $result['questions'][0]['correct_answer']);
    }

    /**
     * Teachers with viewallstats may inspect any match.
     */
    public function test_get_battle_result_allows_teacher(): void {
        $this->resetAfterTest();
        [$course, , $matchid] = $this->setup_match(3);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->setUser($teacher);
        $result = get_battle_result::execute($matchid);
        $this->assertTrue($result['is_completed']);
    }

    /**
     * The view capability is required.
     */
    public function test_view_capability_required(): void {
        $this->resetAfterTest();
        global $DB;
        [$course, $battle, $matchid, $p1] = $this->setup_match(3);

        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('mod/knowledgebattle:view', CAP_PROHIBIT, $roleid,
            \context_module::instance($cm->id), true);

        $this->setUser($p1);
        $this->expectException(\required_capability_exception::class);
        get_battle_result::execute($matchid);
    }

    /**
     * A challenge cannot target a user who is not enrolled in the course.
     */
    public function test_start_battle_rejects_unenrolled_opponent(): void {
        $this->resetAfterTest();
        [, $battle, , , , $other] = $this->setup_match(1);
        $stranger = $this->getDataGenerator()->create_user();

        $this->setUser($other);
        $this->expectException(\moodle_exception::class);
        start_battle::execute($battle->id, 1, $stranger->id);
    }

    /**
     * The number of generated questions is limited.
     */
    public function test_generate_questions_count_limits(): void {
        $this->resetAfterTest();
        [$course, $battle] = $this->setup_match(1);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $this->expectException(\invalid_parameter_exception::class);
        generate_questions::execute($battle->id, 51);
    }

    /**
     * A count below one is rejected too.
     */
    public function test_generate_questions_count_minimum(): void {
        $this->resetAfterTest();
        [$course, $battle] = $this->setup_match(1);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $this->expectException(\invalid_parameter_exception::class);
        generate_questions::execute($battle->id, 0);
    }
}
