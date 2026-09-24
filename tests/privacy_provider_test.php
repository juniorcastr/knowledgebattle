<?php
/**
 * Unit tests for Privacy API provider.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

use advanced_testcase;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_knowledgebattle\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for privacy API provider.
 *
 * @covers \mod_knowledgebattle\privacy\provider
 */
class privacy_provider_test extends provider_testcase {

    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('mod_knowledgebattle');
        $result = provider::get_metadata($collection);
        $this->assertInstanceOf(\core_privacy\local\metadata\collection::class, $result);

        $items = $result->get_collection();
        $this->assertNotEmpty($items);

        $table_names = [];
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $table_names[] = $item->get_name();
            }
        }

        $this->assertContains('knowledgebattle_matches', $table_names);
        $this->assertContains('knowledgebattle_turns', $table_names);
        $this->assertContains('knowledgebattle_user_stats', $table_names);
    }
}
