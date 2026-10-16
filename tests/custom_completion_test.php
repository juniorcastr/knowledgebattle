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
 * Unit tests for custom_completion.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
