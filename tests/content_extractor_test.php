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
 * Unit tests for content_extractor.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for content_extractor class.
 *
 * @covers \mod_knowledgebattle\content_extractor
 */
class content_extractor_test extends advanced_testcase {

    public function test_extract_from_topic() {
        $topic = "Mitocôndria e Respiração Celular";
        $result = content_extractor::extract_from_topic($topic);
        $this->assertEquals($topic, $result);
    }

    public function test_extract_from_topic_with_html() {
        $html = "<p><strong>Biologia Celular:</strong> A mitocôndria produz ATP.</p>";
        $result = content_extractor::extract_from_topic($html);
        $this->assertEquals("Biologia Celular: A mitocôndria produz ATP.", $result);
    }

    public function test_extract_from_section_empty_or_invalid() {
        $result = content_extractor::extract_from_section(99999, 999);
        $this->assertEquals('', $result);
    }

    public function test_extract_from_resource_invalid() {
        $result = content_extractor::extract_from_resource(0);
        $this->assertEquals('', $result);

        $result_nonexistent = content_extractor::extract_from_resource(999999);
        $this->assertEquals('', $result_nonexistent);
    }

    public function test_extract_from_question_bank_empty() {
        $result = content_extractor::extract_from_question_bank(99999);
        $this->assertEquals('', $result);
    }

    public function test_extract_context_scope_topic() {
        $kb = (object)[
            'content_scope' => 1,
            'topic_text' => 'Genética Mendeliana'
        ];
        $result = content_extractor::extract_context($kb, 1);
        $this->assertEquals('Genética Mendeliana', $result);
    }

    public function test_extract_context_scope_section() {
        $kb = (object)[
            'content_scope' => 2,
            'sectionnum' => 999
        ];
        $result = content_extractor::extract_context($kb, 1);
        $this->assertEquals('', $result);
    }

    public function test_extract_context_scope_resource() {
        $kb = (object)[
            'content_scope' => 3,
            'source_cmid' => 99999
        ];
        $result = content_extractor::extract_context($kb, 1);
        $this->assertEquals('', $result);
    }

    public function test_extract_context_scope_question_bank() {
        $kb = (object)[
            'content_scope' => 4
        ];
        $result = content_extractor::extract_context($kb, 99999);
        $this->assertEquals('', $result);
    }

    public function test_extract_context_invalid_scope() {
        $kb = (object)[
            'content_scope' => 99,
            'topic_text' => 'Ignored'
        ];
        $result = content_extractor::extract_context($kb, 1);
        $this->assertEquals('', $result);
    }
}
