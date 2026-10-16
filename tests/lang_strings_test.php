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
 * Checks that every language string used in the code is defined.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle;

defined('MOODLE_INTERNAL') || die();

/**
 * Language string consistency tests.
 *
 * @coversNothing
 */
final class lang_strings_test extends \advanced_testcase {

    /**
     * Loads the strings of a language file.
     *
     * @param string $lang
     * @return array
     */
    private function load_strings(string $lang): array {
        $string = [];
        include(__DIR__ . "/../lang/{$lang}/knowledgebattle.php");
        return $string;
    }

    /**
     * Every key used with get_string() or moodle_exception exists in the English pack.
     */
    public function test_used_strings_exist(): void {
        $strings = $this->load_strings('en');
        $root = dirname(__DIR__);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        $missing = [];
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (!preg_match('/\.(php|js|mustache)$/', $path) || preg_match('#/(tests|lang|amd/build|\.git|\.scratch)/#', $path)) {
                continue;
            }
            $code = file_get_contents($path);
            preg_match_all("/(?:get_string|moodle_exception)\(\s*'([a-z0-9_:]+)'\s*,\s*'mod_knowledgebattle'/", $code, $m1);
            preg_match_all("/\{\{#str\}\}\s*([a-z0-9_:]+)\s*,\s*(?:mod_knowledgebattle|knowledgebattle)/", $code, $m2);
            foreach (array_merge($m1[1], $m2[1]) as $key) {
                if (!isset($strings[$key])) {
                    $missing[$key] = str_replace($root . '/', '', $path);
                }
            }
        }

        $this->assertEmpty($missing, 'Missing strings: ' . json_encode($missing));
    }

    /**
     * The Portuguese pack does not define keys that are unknown to the English pack.
     */
    public function test_translation_has_no_extra_keys(): void {
        $en = $this->load_strings('en');
        $pt = $this->load_strings('pt_br');
        $this->assertSame([], array_values(array_diff(array_keys($pt), array_keys($en))));
    }
}
