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
 * OpenRouter AI Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class openrouter_provider extends base_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        if (!empty($model) && strpos($model, '/') === false) {
            if (stripos($model, 'gemini') !== false) {
                $model = 'google/' . $model;
            } else if (stripos($model, 'llama') !== false) {
                $model = 'meta-llama/' . $model;
            } else if (stripos($model, 'claude') !== false) {
                $model = 'anthropic/' . $model;
            } else if (stripos($model, 'gpt') !== false) {
                $model = 'openai/' . $model;
            } else if (stripos($model, 'deepseek') !== false) {
                $model = 'deepseek/' . $model;
            }
        }
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
