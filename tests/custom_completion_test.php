<?php
/**
 * Unit tests for custom_completion.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use advanced_testcase;
use mod_knowledgebattle\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for custom_completion class.
 *
 * @covers \mod_knowledgebattle\completion\custom_completion
 */
class custom_completion_test extends advanced_testcase {

    public function test_defined_custom_rules() {
        $rules = custom_completion::get_defined_custom_rules();
        $this->assertIsArray($rules);
        $this->assertContains('completionbattles', $rules);
        $this->assertContains('completionwins', $rules);
    }
}
