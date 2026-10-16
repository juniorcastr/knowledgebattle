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
 * Gemini AI Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class gemini_provider extends base_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent" : $baseurl);
    }

    public function get_provider_name(): string {
        return 'Google Gemini';
    }

    public function get_supported_models(): array {
        return [
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-pro'
        ];
    }

    protected function get_api_url(): string {
        $url = $this->baseurl;
        if (strpos($url, '?') === false) {
            $url .= '?key=' . $this->apikey;
        } else {
            $url .= '&key=' . $this->apikey;
        }
        return $url;
    }

    protected function build_headers(): array {
        return [
            'Content-Type' => 'application/json'
        ];
    }

    protected function build_payload(string $prompt): array {
        return [
            'systemInstruction' => [
                'parts' => [['text' => 'You are a JSON generating quiz assistant.']]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ];
    }

    protected function extract_response_text(object $response): string {
        if (!isset($response->candidates[0]->content->parts[0]->text)) {
            throw new \moodle_exception('api_error', 'mod_knowledgebattle', '', 'Unexpected Gemini response.');
        }
        return $response->candidates[0]->content->parts[0]->text;
    }
}
