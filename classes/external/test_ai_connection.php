<?php
/**
 * External API service to test AI provider and model connection.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_knowledgebattle\ai\provider_factory;

class test_ai_connection extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'provider' => new external_value(PARAM_ALPHANUMEXT, 'Provider name', VALUE_DEFAULT, ''),
            'model' => new external_value(PARAM_RAW, 'Model name', VALUE_DEFAULT, ''),
            'apikey' => new external_value(PARAM_RAW, 'API Key', VALUE_DEFAULT, ''),
            'baseurl' => new external_value(PARAM_RAW, 'Base URL', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute connection test.
     *
     * @param string $provider
     * @param string $model
     * @param string $apikey
     * @param string $baseurl
     * @return array
     */
    public static function execute(
        string $provider = '',
        string $model = '',
        string $apikey = '',
        string $baseurl = ''
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'provider' => $provider,
            'model' => $model,
            'apikey' => $apikey,
            'baseurl' => $baseurl,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $providername = trim($params['provider']);
        $modelname = trim($params['model']);
        $key = trim($params['apikey']);
        $url = trim($params['baseurl']);

        if (empty($providername)) {
            $providername = get_config('mod_knowledgebattle', 'ai_provider') ?: 'openrouter';
        }
        if (empty($modelname)) {
            $modelname = get_config('mod_knowledgebattle', 'ai_model');
            if (empty($modelname)) {
                $modelname = ($providername === 'openrouter') ? 'google/gemini-2.5-flash-lite' : 'gpt-3.5-turbo';
            }
        }
        if (empty($key)) {
            $key = get_config('mod_knowledgebattle', $providername . '_apikey');
        }
        if ($providername !== 'local_llm') {
            $specificurl = get_config('mod_knowledgebattle', $providername . '_baseurl');
            $url = !empty($specificurl) ? $specificurl : '';
        } else if (empty($url)) {
            $url = get_config('mod_knowledgebattle', 'local_llm_baseurl') ?: 'http://localhost:11434';
        }

        if ($providername !== 'local_llm' && empty($key)) {
            return [
                'success' => false,
                'latency' => 0,
                'provider' => $providername,
                'model' => $modelname,
                'http_code' => 0,
                'reply' => '',
                'error' => get_string('test_ai_connection_no_key', 'mod_knowledgebattle'),
            ];
        }

        try {
            $provider_instance = provider_factory::create($providername, (string)$key, (string)$modelname, (string)$url);
            return $provider_instance->test_connection_detailed();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'latency' => 0,
                'provider' => $providername,
                'model' => $modelname,
                'http_code' => 0,
                'reply' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if test succeeded'),
            'latency' => new external_value(PARAM_INT, 'Latency in milliseconds'),
            'provider' => new external_value(PARAM_RAW, 'Provider display name'),
            'model' => new external_value(PARAM_RAW, 'Model tested'),
            'http_code' => new external_value(PARAM_INT, 'HTTP status code'),
            'reply' => new external_value(PARAM_RAW, 'Reply summary or status'),
            'error' => new external_value(PARAM_RAW, 'Error message if any'),
        ]);
    }
}
