<?php
/**
 * Claude AI Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class claude_provider extends base_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'https://api.anthropic.com/v1/messages' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'Anthropic Claude';
    }

    public function get_supported_models(): array {
        return [
            'claude-3-7-sonnet-20250219',
            'claude-3-5-sonnet-20241022',
            'claude-3-5-haiku-20241022'
        ];
    }

    protected function get_api_url(): string {
        return $this->baseurl;
    }

    protected function build_headers(): array {
        return [
            'x-api-key' => $this->apikey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json'
        ];
    }

    protected function build_payload(string $prompt): array {
        return [
            'model' => $this->model,
            'system' => 'You are a json generation assistant. Always output valid JSON.',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 4096
        ];
    }

    protected function extract_response_text(object $response): string {
        if (!isset($response->content[0]->text)) {
            throw new \moodle_exception('api_error', 'mod_knowledgebattle', '', 'Unexpected Claude response.');
        }
        return $response->content[0]->text;
    }
}
