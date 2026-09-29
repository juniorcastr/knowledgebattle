<?php
/**
 * Base AI Provider for Knowledge Battle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

/**
 * Class base_provider
 * @package mod_knowledgebattle\ai
 */
abstract class base_provider implements provider_interface {
    /** @var string */
    protected $apikey;

    /** @var string */
    protected $model;

    /** @var string */
    protected $baseurl;

    /** @var int */
    protected $timeout = 60;

    /**
     * base_provider constructor.
     *
     * @param string $apikey
     * @param string $model
     * @param string $baseurl
     */
    public function __construct(string $apikey, string $model, string $baseurl = '') {
        $this->apikey = $apikey;
        $this->model = $model;
        $this->baseurl = $baseurl;
    }

    /**
     * Makes an HTTP request using Moodle's curl class with retry logic.
     *
     * @param string $url
     * @param array $payload
     * @param array $headers
     * @return object
     * @throws \moodle_exception
     */
    protected function make_request(string $url, array $payload, array $headers = []): object {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl(['ignoresecurity' => true]);
        $curl_headers = [];
        foreach ($headers as $k => $v) {
            $curl_headers[] = is_int($k) ? $v : "$k: $v";
        }

        $options = [
            'CURLOPT_TIMEOUT' => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_RETURNTRANSFER' => true
        ];

        $attempt = 0;
        $max_attempts = 3;

        while ($attempt < $max_attempts) {
            $curl->setHeader($curl_headers);
            $raw_response = $curl->post($url, json_encode($payload), $options);
            $info = $curl->get_info();

            if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
                $decoded = json_decode($raw_response);
                if ($decoded) {
                    return $decoded;
                }
            }

            $attempt++;
            if ($attempt < $max_attempts) {
                sleep(pow(2, $attempt));
            }
        }

        throw new \moodle_exception('api_error', 'mod_knowledgebattle', '', 'Failed after 3 attempts');
    }

    /**
     * Builds the system and user prompt for quiz generation.
     *
     * @param string $context
     * @param int $count
     * @param string $difficulty
     * @param string $language
     * @return string
     */
    protected function build_quiz_prompt(string $context, int $count, string $difficulty, string $language): string {
        return "You are an expert quiz generator. Generate exactly {$count} multiple-choice questions in {$language}.
Difficulty: {$difficulty}.
Context: \"{$context}\"

You must respond ONLY with a valid JSON object matching exactly this schema:
{
  \"questions\": [
    {
      \"question\": \"The question text\",
      \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
      \"correct_index\": 0,
      \"explanation\": \"Why this is correct\",
      \"difficulty\": \"{$difficulty}\"
    }
  ]
}
Do not include markdown blocks or any other text, just the JSON.";
    }

    /**
     * Parses the raw AI response, extracts JSON, and validates.
     *
     * @param string $raw_response
     * @return array
     * @throws \moodle_exception
     */
    protected function parse_quiz_response(string $raw_response): array {
        // Strip markdown code blocks if present
        $raw_response = preg_replace('/^```(?:json)?\s*/i', '', trim($raw_response));
        $raw_response = preg_replace('/\s*```$/', '', $raw_response);

        $data = json_decode($raw_response);
        if (!$data || !isset($data->questions) || !is_array($data->questions)) {
            throw new \moodle_exception('invalid_json_response', 'mod_knowledgebattle');
        }

        $valid_questions = [];
        foreach ($data->questions as $q) {
            if ($this->validate_question($q)) {
                $valid_questions[] = $q;
            }
        }

        return $valid_questions;
    }

    /**
     * Validates a single question object.
     *
     * @param object $question
     * @return bool
     */
    protected function validate_question(object $question): bool {
        if (empty($question->question) || empty($question->options) || !is_array($question->options)) {
            return false;
        }
        if (count($question->options) !== 4) {
            return false;
        }
        if (!isset($question->correct_index) || !is_int($question->correct_index) || $question->correct_index < 0 || $question->correct_index > 3) {
            return false;
        }
        return true;
    }

    /**
     * Template method to generate the quiz. Subclasses must implement the abstract methods to format the API call.
     */
    public function generate_quiz(string $context, int $count, string $difficulty = 'medium', string $language = 'pt-BR'): array {
        $prompt = $this->build_quiz_prompt($context, $count, $difficulty, $language);
        $payload = $this->build_payload($prompt);
        $headers = $this->build_headers();
        $url = $this->get_api_url();
        
        $response = $this->make_request($url, $payload, $headers);
        $raw_text = $this->extract_response_text($response);
        
        return $this->parse_quiz_response($raw_text);
    }

    /**
     * Builds the payload specifically for testing connection.
     *
     * @param string $prompt
     * @return array
     */
    protected function build_test_payload(string $prompt): array {
        $payload = $this->build_payload($prompt);
        $payload['max_tokens'] = 30;
        if (isset($payload['generationConfig']) && is_array($payload['generationConfig'])) {
            $payload['generationConfig']['maxOutputTokens'] = 30;
        }
        return $payload;
    }

    /**
     * Tests the connection with a minimal prompt.
     *
     * @return bool
     */
    public function test_connection(): bool {
        $res = $this->test_connection_detailed();
        return !empty($res['success']);
    }

    /**
     * Tests the connection with detailed diagnostic information.
     *
     * @return array
     */
    public function test_connection_detailed(): array {
        $starttime = microtime(true);

        if (empty($this->apikey) && !($this instanceof local_llm_provider)) {
            return [
                'success' => false,
                'latency' => 0,
                'provider' => $this->get_provider_name(),
                'model' => $this->model,
                'http_code' => 0,
                'reply' => '',
                'error' => get_string('test_ai_connection_no_key', 'mod_knowledgebattle')
            ];
        }

        try {
            $prompt = 'Respond with JSON: {"status": "ok"}';
            $payload = $this->build_test_payload($prompt);
            $headers = $this->build_headers();
            $url = $this->get_api_url();

            global $CFG;
            require_once($CFG->libdir . '/filelib.php');

            $curl = new \curl(['ignoresecurity' => true]);
            $curl_headers = [];
            foreach ($headers as $k => $v) {
                $curl_headers[] = is_int($k) ? $v : "$k: $v";
            }
            $curl->setHeader($curl_headers);

            $options = [
                'CURLOPT_TIMEOUT' => 15,
                'CURLOPT_CONNECTTIMEOUT' => 8,
                'CURLOPT_RETURNTRANSFER' => true
            ];

            $raw_response = $curl->post($url, json_encode($payload), $options);
            $latency = (int) round((microtime(true) - $starttime) * 1000);
            $info = $curl->get_info();
            $http_code = (int) ($info['http_code'] ?? 0);

            if ($http_code >= 200 && $http_code < 300) {
                $reply = '';
                $decoded = json_decode($raw_response);
                if ($decoded) {
                    try {
                        $reply = $this->extract_response_text($decoded);
                    } catch (\Throwable $e) {
                        $reply = '{"status":"ok"}';
                    }
                }
                return [
                    'success' => true,
                    'latency' => $latency,
                    'provider' => $this->get_provider_name(),
                    'model' => $this->model,
                    'http_code' => $http_code,
                    'reply' => mb_substr(trim($reply), 0, 150),
                    'error' => ''
                ];
            }

            // Error diagnosis
            $errormsg = '';
            if (!empty($curl->error)) {
                $errormsg = $curl->error;
            } else if (!empty($raw_response)) {
                $decoded = json_decode($raw_response);
                if (isset($decoded->error->message)) {
                    $errormsg = $decoded->error->message;
                } else if (isset($decoded->error) && is_string($decoded->error)) {
                    $errormsg = $decoded->error;
                } else if (isset($decoded->message)) {
                    $errormsg = $decoded->message;
                } else {
                    $errormsg = mb_substr(strip_tags($raw_response), 0, 200);
                }
            }

            if (empty($errormsg)) {
                $errormsg = 'HTTP ' . ($http_code ?: 'Unknown Error');
            } else if ($http_code > 0 && strpos($errormsg, (string)$http_code) === false) {
                $errormsg = "HTTP {$http_code}: {$errormsg}";
            }

            return [
                'success' => false,
                'latency' => $latency,
                'provider' => $this->get_provider_name(),
                'model' => $this->model,
                'http_code' => $http_code,
                'reply' => '',
                'error' => $errormsg
            ];

        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $starttime) * 1000);
            return [
                'success' => false,
                'latency' => $latency,
                'provider' => $this->get_provider_name(),
                'model' => $this->model,
                'http_code' => 0,
                'reply' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    abstract protected function get_api_url(): string;
    abstract protected function build_headers(): array;
    abstract protected function build_payload(string $prompt): array;
    abstract protected function extract_response_text(object $response): string;
}
