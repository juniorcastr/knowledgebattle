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
 * Groq Provider.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
