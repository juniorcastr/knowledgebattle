<?php
/**
 * External function manage_question.
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

defined('MOODLE_INTERNAL') || die();

class manage_question extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'action' => new external_value(PARAM_ALPHA, 'Action: approve, discard, edit'),
            'question_text' => new external_value(PARAM_RAW, 'Question text', VALUE_DEFAULT, ''),
            'options_json' => new external_value(PARAM_RAW, 'Options JSON array', VALUE_DEFAULT, ''),
            'correct_index' => new external_value(PARAM_INT, 'Correct option index', VALUE_DEFAULT, 0),
            'explanation' => new external_value(PARAM_RAW, 'Explanation text', VALUE_DEFAULT, '')
        ]);
    }

    public static function execute($questionid, $action, $question_text = '', $options_json = '', $correct_index = 0, $explanation = '') {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'action' => $action,
            'question_text' => $question_text,
            'options_json' => $options_json,
            'correct_index' => $correct_index,
            'explanation' => $explanation
        ]);

        $question = $DB->get_record('knowledgebattle_questions', ['id' => $params['questionid']], '*', MUST_EXIST);
        $battle = $DB->get_record('knowledgebattle', ['id' => $question->battleid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('knowledgebattle', $battle->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/knowledgebattle:managequestions', $context);

        $question->timemodified = time();

        if ($params['action'] === 'approve') {
            $question->status = 1; // Approved
            $DB->update_record('knowledgebattle_questions', $question);

            if (class_exists('\mod_knowledgebattle\event\question_approved')) {
                $event = \mod_knowledgebattle\event\question_approved::create([
                    'objectid' => $question->id,
                    'context' => $context,
                    'other' => ['battleid' => $battle->id]
                ]);
                $event->trigger();
            }
        } else if ($params['action'] === 'discard') {
            $question->status = 2; // Discarded
            $DB->update_record('knowledgebattle_questions', $question);
        } else if ($params['action'] === 'edit') {
            if (!empty($params['question_text'])) {
                $question->question_text = $params['question_text'];
            }
            if (!empty($params['options_json'])) {
                $question->options_json = $params['options_json'];
            }
            $question->correct_index = (int)$params['correct_index'];
            if (isset($params['explanation'])) {
                $question->explanation = $params['explanation'];
            }
            $question->status = 1; // When edited and saved by teacher/admin, mark as approved
            $DB->update_record('knowledgebattle_questions', $question);
        } else {
            throw new \invalid_parameter_exception('Invalid action parameter');
        }

        return [
            'success' => true
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag')
        ]);
    }
}
