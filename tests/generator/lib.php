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
 * Data generator for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @category   test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generator of Knowledge Battle activities and related records.
 */
class mod_knowledgebattle_generator extends testing_module_generator {

    /**
     * Creates an activity instance.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'ai_provider' => 'openrouter',
            'ai_model' => '',
            'content_scope' => 1,
            'topic_text' => 'Test topic',
            'supply_mode' => 1,
            'pool_size' => 0,
            'questions_per_match' => 2,
            'time_per_question' => 30,
            'wo_timeout_hours' => 24,
            'win_points' => 100,
            'draw_points' => 30,
            'loss_points' => -20,
            'allow_negative_points' => 0,
            'max_daily_battles' => 5,
            'bot_enabled' => 1,
            'ranking_visibility' => 1,
            'grade_criteria' => 1,
            'grade' => 100,
        ];
        foreach ($defaults as $name => $value) {
            if (!isset($record->$name)) {
                $record->$name = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Creates an approved question for an activity.
     *
     * @param int $battleid
     * @param array $overrides
     * @return stdClass
     */
    public function create_question(int $battleid, array $overrides = []): stdClass {
        global $DB;

        $now = time();
        $record = (object) ($overrides + [
            'battleid' => $battleid,
            'question_text' => 'What is 2 + 2?',
            'options_json' => json_encode(['3', '4', '5', '6']),
            'correct_index' => 1,
            'explanation' => 'Basic arithmetic.',
            'difficulty' => 'medium',
            'status' => 1,
            'source_type' => 'ai_generated',
            'source_question_id' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $record->id = $DB->insert_record('knowledgebattle_questions', $record);
        return $record;
    }
}
