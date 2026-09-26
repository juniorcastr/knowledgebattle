<?php
/**
 * OpenAI Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class openai_provider extends base_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'https://api.openai.com/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'OpenAI';
    }

    public function get_supported_models(): array {
        return [
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-4-turbo'
        ];
    }

    protected function get_api_url(): string {
        return $this->baseurl;
    }

    protected function build_headers(): array {
        return [
            'Authorization' => 'Bearer ' . $this->apikey,
            'Content-Type' => 'application/json'
        ];
    }

    protected function build_payload(string $prompt): array {
        return [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a quiz generation assistant.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ];
    }

    protected function extract_response_text(object $response): string {
        if (!isset($response->choices[0]->message->content)) {
            throw new \moodle_exception('api_error', 'mod_knowledgebattle', '', 'Unexpected OpenAI response.');
        }
        return $response->choices[0]->message->content;
    }
}
