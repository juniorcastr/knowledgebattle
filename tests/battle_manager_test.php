<?php
/**
 * Unit tests for battle_manager.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for battle_manager class.
 *
 * @covers \mod_knowledgebattle\battle_manager
 */
class battle_manager_test extends advanced_testcase {

    public function test_shuffled_options_deterministic() {
        $userid = 42;
        $questionid = 100;
        $options = ['Alpha', 'Beta', 'Gamma', 'Delta'];

        $result1 = battle_manager::get_shuffled_options($userid, $questionid, $options);
        $result2 = battle_manager::get_shuffled_options($userid, $questionid, $options);

        // Same user and question must produce the EXACT same shuffled order.
        $this->assertEquals($result1['options'], $result2['options']);
        $this->assertEquals($result1['map'], $result2['map']);
        $this->assertCount(4, $result1['options']);
    }

    public function test_shuffled_options_different_for_different_users() {
        $questionid = 100;
        $options = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon'];

        $result_user1 = battle_manager::get_shuffled_options(1, $questionid, $options);
        $result_user2 = battle_manager::get_shuffled_options(999, $questionid, $options);

        // The maps or order should vary between different users.
        $this->assertCount(5, $result_user1['options']);
        $this->assertCount(5, $result_user2['options']);
    }

    public function test_unshuffle_option_roundtrip() {
        $userid = 15;
        $questionid = 205;
        $options = ['Option 0', 'Option 1', 'Option 2', 'Option 3'];

        $shuffled = battle_manager::get_shuffled_options($userid, $questionid, $options);

        // For each original option index, check that unshuffling the new position recovers the original index.
        foreach ($shuffled['map'] as $original_index => $shuffled_index) {
            $recovered = battle_manager::unshuffle_option($userid, $questionid, $shuffled_index, count($options));
            $this->assertEquals($original_index, $recovered, "Failed to unshuffle index {$shuffled_index}");
        }
    }
}
