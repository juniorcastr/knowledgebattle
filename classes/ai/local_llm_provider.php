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
 * Local LLM Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class local_llm_provider extends openai_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        $baseurl = trim($baseurl);
        if (!empty($baseurl)) {
            $baseurl = rtrim($baseurl, '/');
            if (strpos($baseurl, '/chat/completions') === false) {
                if (substr($baseurl, -3) === '/v1') {
                    $baseurl .= '/chat/completions';
                } else {
                    $baseurl .= '/v1/chat/completions';
                }
            }
        }
        parent::__construct($apikey, $model, empty($baseurl) ? 'http://localhost:11434/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'Local LLM (OpenAI Compatible)';
    }

    public function get_supported_models(): array {
        return [];
    }
    
    public function test_connection(): bool {
        $res = $this->test_connection_detailed();
        return !empty($res['success']);
    }

    public function test_connection_detailed(): array {
        $starttime = microtime(true);
        // Ping models endpoint
        $url = str_replace('/chat/completions', '/models', $this->get_api_url());
        
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader($this->build_headers());
        $raw_response = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 8, 'CURLOPT_CONNECTTIMEOUT' => 4]);
        $latency = (int) round((microtime(true) - $starttime) * 1000);
        $info = $curl->get_info();
        $http_code = (int) ($info['http_code'] ?? 0);

        if ($http_code >= 200 && $http_code < 300) {
            $decoded = json_decode($raw_response);
            $modelcount = 0;
            if (isset($decoded->data) && is_array($decoded->data)) {
                $modelcount = count($decoded->data);
            }
            return [
                'success' => true,
                'latency' => $latency,
                'provider' => $this->get_provider_name(),
                'model' => $this->model,
                'http_code' => $http_code,
                'reply' => $modelcount > 0 ? get_string('local_llm_models_found', 'mod_knowledgebattle', $modelcount) : get_string('local_llm_responding', 'mod_knowledgebattle'),
                'error' => ''
            ];
        }

        // Only report the cURL error or the HTTP status, never the response body.
        $err = !empty($curl->error) ? $curl->error : "HTTP {$http_code}";
        return [
            'success' => false,
            'latency' => $latency,
            'provider' => $this->get_provider_name(),
            'model' => $this->model,
            'http_code' => $http_code,
            'reply' => '',
            'error' => $err
        ];
    }
}
