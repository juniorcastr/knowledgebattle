<?php
/**
 * Unit tests for grade_calculator.
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
 * Unit tests for grade_calculator class.
 *
 * @covers \mod_knowledgebattle\grade_calculator
 */
class grade_calculator_test extends advanced_testcase {

    public function test_grade_zero_returns_null() {
        $battle = (object)[
            'id' => 1,
            'grade' => 0,
            'grade_criteria' => 1
        ];

        $grade = grade_calculator::calculate_grade($battle, 999);
        $this->assertNull($grade);
    }
}
