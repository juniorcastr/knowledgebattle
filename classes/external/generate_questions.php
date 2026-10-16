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
 * External function generate_questions.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use mod_knowledgebattle\content_extractor;
use mod_knowledgebattle\ai\provider_factory;

defined('MOODLE_INTERNAL') || die();

class generate_questions extends external_api {

    /** Maximum number of questions that can be requested in one call. */
    const MAX_COUNT = 50;

    /** Default hourly limit of generation requests per user. */
    const DEFAULT_HOURLY_LIMIT = 10;

    public static function execute_parameters() {
        return new external_function_parameters([
            'battleid' => new external_value(PARAM_INT, 'Battle ID'),
            'count' => new external_value(PARAM_INT, 'Number of questions (1 to 50)', VALUE_DEFAULT, 10)
        ]);
    }

    public static function execute($battleid, $count = 10) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid,
            'count' => $count
        ]);

        if ($params['count'] < 1 || $params['count'] > self::MAX_COUNT) {
            throw new \invalid_parameter_exception('count must be between 1 and ' . self::MAX_COUNT);
        }

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/knowledgebattle:generatequestions', $context);

        self::enforce_rate_limit((int) $USER->id);

        // Extract context using content extractor.
        $text_context = content_extractor::extract_context($battle, (int)$battle->course);
        if (empty(trim($text_context))) {
            $text_context = $battle->name . ' - ' . strip_tags($battle->intro ?? '');
        }

        // Create configured AI provider.
        $provider = provider_factory::create_from_config($battle);

        // Generate quiz questions.
        $questions = $provider->generate_quiz(
            $text_context,
            $params['count'],
            'medium',
            self::get_language_name($battle)
        );

        $generated_count = 0;
        $now = time();

        foreach ($questions as $q) {
            if (empty($q->question) || !isset($q->options) || !isset($q->correct_index)) {
                continue;
            }

            $record = new \stdClass();
            $record->battleid = $battle->id;
            $record->question_text = $q->question;
            $record->options_json = json_encode($q->options, JSON_UNESCAPED_UNICODE);
            $record->correct_index = (int)$q->correct_index;
            $record->explanation = $q->explanation ?? '';
            $record->difficulty = $q->difficulty ?? 'medium';
            $record->status = 0; // Pending approval
            $record->source_type = 'ai_generated';
            $record->source_question_id = 0;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $DB->insert_record('knowledgebattle_questions', $record);
            $generated_count++;
        }

        // Trigger event.
        if (class_exists('\mod_knowledgebattle\event\questions_generated')) {
            $event = \mod_knowledgebattle\event\questions_generated::create([
                'objectid' => $battle->id,
                'context' => $context,
                'other' => [
                    'count' => $generated_count,
                    'provider' => $battle->ai_provider ?? 'openrouter'
                ]
            ]);
            $event->trigger();
        }

        $total_pool_size = $DB->count_records('knowledgebattle_questions', ['battleid' => $battle->id]);

        return [
            'generated_count' => (int)$generated_count,
            'total_pool_size' => (int)$total_pool_size
        ];
    }

    /**
     * Applies the per-user hourly limit configured in the plugin settings.
     *
     * @param int $userid
     * @throws \moodle_exception When the limit has been reached.
     */
    protected static function enforce_rate_limit(int $userid): void {
        $limit = (int) get_config('mod_knowledgebattle', 'antiflood_limit');
        if ($limit <= 0) {
            $limit = self::DEFAULT_HOURLY_LIMIT;
        }

        $now = time();
        $windowstart = (int) get_user_preferences('mod_knowledgebattle_gen_window', 0, $userid);
        $used = (int) get_user_preferences('mod_knowledgebattle_gen_count', 0, $userid);

        if ($windowstart <= 0 || ($now - $windowstart) >= HOURSECS) {
            $windowstart = $now;
            $used = 0;
        }
        if ($used >= $limit) {
            throw new \moodle_exception('error_generation_limit', 'mod_knowledgebattle', '', $limit);
        }

        set_user_preference('mod_knowledgebattle_gen_window', $windowstart, $userid);
        set_user_preference('mod_knowledgebattle_gen_count', $used + 1, $userid);
    }

    /**
     * Returns the name of the course language (or the current one) for the AI prompt.
     *
     * @param \stdClass $battle
     * @return string
     */
    protected static function get_language_name(\stdClass $battle): string {
        global $DB;

        $code = (string) $DB->get_field('course', 'lang', ['id' => $battle->course]);
        if ($code === '') {
            $code = current_language();
        }
        $translations = get_string_manager()->get_list_of_translations();
        if (!empty($translations[$code])) {
            // Labels look like "Português - Brasil (pt_br)"; the model only needs the name.
            return trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $translations[$code]));
        }
        return $code;
    }

    public static function execute_returns() {
        return new external_single_structure([
            'generated_count' => new external_value(PARAM_INT, 'Number of questions generated'),
            'total_pool_size' => new external_value(PARAM_INT, 'Total questions currently in pool')
        ]);
    }
}
