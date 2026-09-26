<?php
/**
 * Unit tests for content_extractor.
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
}
