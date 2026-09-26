<?php
/**
 * Groq Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class groq_provider extends openai_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'https://api.groq.com/openai/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'Groq';
    }

    public function get_supported_models(): array {
        return [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'mixtral-8x7b-32768'
        ];
    }
}
