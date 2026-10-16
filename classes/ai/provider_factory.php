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
 * Provider Factory.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio / Knowledge Battle
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_knowledgebattle\ai;

defined('MOODLE_INTERNAL') || die();

class provider_factory {

    /**
     * Creates an AI provider instance.
     *
     * @param string $provider_name
     * @param string $apikey
     * @param string $model
     * @param string $baseurl
     * @return provider_interface
     * @throws \moodle_exception
     */
    public static function create(string $provider_name, string $apikey, string $model, string $baseurl = ''): provider_interface {
        switch ($provider_name) {
            case 'openrouter':
                return new openrouter_provider($apikey, $model, $baseurl);
            case 'openai':
                return new openai_provider($apikey, $model, $baseurl);
            case 'gemini':
                return new gemini_provider($apikey, $model, $baseurl);
            case 'claude':
                return new claude_provider($apikey, $model, $baseurl);
            case 'deepseek':
                return new deepseek_provider($apikey, $model, $baseurl);
            case 'groq':
                return new groq_provider($apikey, $model, $baseurl);
            case 'local_llm':
                return new local_llm_provider($apikey, $model, $baseurl);
            default:
                throw new \moodle_exception('unknown_provider', 'mod_knowledgebattle', '', $provider_name);
        }
    }

    /**
     * Creates a provider from plugin configuration or instance data.
     *
     * @param object|null $instance
     * @return provider_interface
     */
    public static function create_from_config(?object $instance = null): provider_interface {
        if ($instance && !empty($instance->ai_provider) && !empty($instance->ai_model)) {
            $provider_name = $instance->ai_provider;
            $model = $instance->ai_model;
        } else {
            $provider_name = get_config('mod_knowledgebattle', 'ai_provider');
            if (empty($provider_name)) {
                $provider_name = get_config('mod_knowledgebattle', 'default_ai_provider');
            }
            $model = get_config('mod_knowledgebattle', 'ai_model');
            if (empty($model)) {
                $model = get_config('mod_knowledgebattle', 'default_ai_model');
            }
        }

        if (empty($provider_name)) {
            $provider_name = 'openrouter'; // default fallback
        }

        if (empty($model)) {
            $model = ($provider_name === 'openrouter') ? 'google/gemini-2.5-flash' : 'gpt-3.5-turbo';
        }

        $apikey = get_config('mod_knowledgebattle', $provider_name . '_apikey');
        if (empty($apikey)) {
            $apikey = get_config('mod_knowledgebattle', 'apikey_' . $provider_name);
        }

        $baseurl = get_config('mod_knowledgebattle', $provider_name . '_baseurl');
        if (empty($baseurl)) {
            $baseurl = get_config('mod_knowledgebattle', 'baseurl_' . $provider_name);
        }

        return self::create($provider_name, (string)$apikey, (string)$model, (string)$baseurl);
    }
}
