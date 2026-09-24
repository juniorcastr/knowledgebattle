<?php
/**
 * DeepSeek Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class deepseek_provider extends openai_provider {

    public function __construct(string $apikey, string $model, string $baseurl = '') {
        parent::__construct($apikey, $model, empty($baseurl) ? 'https://api.deepseek.com/v1/chat/completions' : $baseurl);
    }

    public function get_provider_name(): string {
        return 'DeepSeek';
    }

    public function get_supported_models(): array {
        return [
            'deepseek-chat',
            'deepseek-reasoner'
        ];
    }
}
