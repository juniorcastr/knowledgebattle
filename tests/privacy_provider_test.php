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
 * Unit tests for Privacy API provider.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_knowledgebattle\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for privacy API provider.
 *
 * @covers \mod_knowledgebattle\privacy\provider
 */
final class privacy_provider_test extends provider_testcase {

    /**
     * Seeds a course, activity, two players, a match, turns and user stats.
     *
     * @return array
     */
    private function seed_activity_data(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_knowledgebattle');
        $battle = $gen->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id);
        $context = \context_module::instance($cm->id);

        $u1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $u2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $q = $gen->create_question($battle->id);

        $now = time();
        $matchid = $DB->insert_record('knowledgebattle_matches', (object) [
            'battleid' => $battle->id,
            'player1_id' => $u1->id,
            'player2_id' => $u2->id,
            'match_type' => 1,
            'is_bot_match' => 0,
            'status' => 3,
            'winner_id' => $u1->id,
            'p1_score' => 5,
            'p2_score' => 3,
            'p1_time_ms' => 1200,
            'p2_time_ms' => 1800,
            'timestarted' => $now,
            'timecompleted' => $now,
            'timeexpire' => $now + 3600,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('knowledgebattle_match_questions', (object) [
            'matchid' => $matchid,
            'questionid' => $q->id,
            'question_order' => 1,
        ]);

        $DB->insert_record('knowledgebattle_turns', (object) [
            'matchid' => $matchid,
            'userid' => $u1->id,
            'questionid' => $q->id,
            'selected_option' => 1,
            'is_correct' => 1,
            'response_time_ms' => 450,
            'timecreated' => $now,
        ]);

        $DB->insert_record('knowledgebattle_user_stats', (object) [
            'battleid' => $battle->id,
            'userid' => $u1->id,
            'current_points' => 100,
            'matches_played' => 1,
            'wins' => 1,
            'losses' => 0,
            'draws' => 0,
            'current_streak' => 1,
            'max_streak' => 1,
            'last_battle_date' => $now,
            'daily_battles_count' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return [$battle, $context, $u1, $u2, $matchid];
    }

    /**
     * Tests metadata declaration.
     */
    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('mod_knowledgebattle');
        $result = provider::get_metadata($collection);
        $this->assertInstanceOf(\core_privacy\local\metadata\collection::class, $result);

        $items = $result->get_collection();
        $this->assertNotEmpty($items);

        $tablenames = [];
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tablenames[] = $item->get_name();
            }
        }

        $this->assertContains('knowledgebattle_matches', $tablenames);
        $this->assertContains('knowledgebattle_turns', $tablenames);
        $this->assertContains('knowledgebattle_user_stats', $tablenames);
    }

    /**
     * Tests context discovery, userlist discovery, data export and deletion.
     */
    public function test_privacy_lifecycle(): void {
        global $DB;
        $this->resetAfterTest();

        [$battle, $context, $u1, $u2, $matchid] = $this->seed_activity_data();

        // 1. Context discovery.
        $contextlist = provider::get_contexts_for_userid($u1->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->current()->id);

        // 2. Users in context.
        $userlist = new userlist($context, 'mod_knowledgebattle');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertContains((int) $u1->id, $userids);
        $this->assertContains((int) $u2->id, $userids);

        // 3. Export user data.
        $approved = new approved_contextlist($u1, 'mod_knowledgebattle', [$context->id]);
        provider::export_user_data($approved);
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        // 4. Delete data for single user.
        provider::delete_data_for_user($approved);
        $this->assertEquals(0, $DB->count_records('knowledgebattle_user_stats',
            ['battleid' => $battle->id, 'userid' => $u1->id]));
        $this->assertEquals(0, $DB->count_records('knowledgebattle_turns', ['matchid' => $matchid]));
        $this->assertEquals(0, $DB->count_records('knowledgebattle_matches', ['id' => $matchid]));
    }

    /**
     * Tests delete_data_for_all_users_in_context and delete_data_for_users.
     */
    public function test_bulk_deletion(): void {
        global $DB;
        $this->resetAfterTest();

        [$battle, $context, $u1] = $this->seed_activity_data();

        $approvedusers = new approved_userlist($context, 'mod_knowledgebattle', [$u1->id]);
        provider::delete_data_for_users($approvedusers);
        $this->assertEquals(0, $DB->count_records('knowledgebattle_matches', ['battleid' => $battle->id]));

        // Re-seed and test delete_data_for_all_users_in_context.
        [$battle2, $context2] = $this->seed_activity_data();
        provider::delete_data_for_all_users_in_context($context2);
        $this->assertEquals(0, $DB->count_records('knowledgebattle_matches', ['battleid' => $battle2->id]));
        $this->assertEquals(0, $DB->count_records('knowledgebattle_user_stats', ['battleid' => $battle2->id]));
    }
}
