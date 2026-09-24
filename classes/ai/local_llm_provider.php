<?php
/**
 * Local LLM Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class local_llm_provider extends openai_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'http://localhost:11434/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'Local LLM (OpenAI Compatible)';
    }

    public function get_supported_models(): array {
        return [];
    }
    
    public function test_connection(): bool {
        // Ping models endpoint
        $url = str_replace('/chat/completions', '/models', $this->get_api_url());
        
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader($this->build_headers());
        $response = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 10]);
        $info = $curl->get_info();
        return ($info['http_code'] >= 200 && $info['http_code'] < 300);
    }
}
