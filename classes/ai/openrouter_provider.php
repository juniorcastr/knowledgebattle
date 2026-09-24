<?php
/**
 * OpenRouter AI Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class openrouter_provider extends base_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'https://openrouter.ai/api/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'OpenRouter';
    }

    public function get_supported_models(): array {
        return [
            'meta-llama/llama-3.3-70b-instruct',
            'deepseek/deepseek-chat',
            'google/gemini-2.0-flash-exp',
            'anthropic/claude-3.5-sonnet'
        ];
    }

    protected function get_api_url(): string {
        return $this->baseurl;
    }

    protected function build_headers(): array {
        global $CFG;
        return [
            'Authorization' => 'Bearer ' . $this->apikey,
            'HTTP-Referer' => $CFG->wwwroot,
            'X-Title' => 'Battle Quiz - Moodle',
            'Content-Type' => 'application/json'
        ];
    }

    protected function build_payload(string $prompt): array {
        return [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an AI assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ];
    }

    protected function extract_response_text(object $response): string {
        if (!isset($response->choices[0]->message->content)) {
            throw new \moodle_exception('api_error', 'mod_knowledgebattle', '', 'Unexpected response format.');
        }
        return $response->choices[0]->message->content;
    }
}
