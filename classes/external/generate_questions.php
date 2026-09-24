<?php
/**
 * External function generate_questions.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

    public static function execute_parameters() {
        return new external_function_parameters([
            'battleid' => new external_value(PARAM_INT, 'Battle ID'),
            'count' => new external_value(PARAM_INT, 'Number of questions', VALUE_DEFAULT, 10)
        ]);
    }

    public static function execute($battleid, $count = 10) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'battleid' => $battleid,
            'count' => $count
        ]);

        $battle = $DB->get_record('knowledgebattle', ['id' => $params['battleid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/knowledgebattle:generatequestions', $context);

        // Extract context using content extractor.
        $text_context = content_extractor::extract_context($battle, (int)$battle->course);
        if (empty(trim($text_context))) {
            $text_context = $battle->name . ' - ' . strip_tags($battle->intro ?? '');
        }

        // Create configured AI provider.
        $provider = provider_factory::create_from_config($battle);

        // Generate quiz questions.
        $questions = $provider->generate_quiz($text_context, $params['count']);

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

    public static function execute_returns() {
        return new external_single_structure([
            'generated_count' => new external_value(PARAM_INT, 'Number of questions generated'),
            'total_pool_size' => new external_value(PARAM_INT, 'Total questions currently in pool')
        ]);
    }
}
