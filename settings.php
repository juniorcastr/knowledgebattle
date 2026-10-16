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
 * Settings for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    global $OUTPUT, $PAGE;

    $providers = [];
    foreach (['openrouter', 'openai', 'gemini', 'claude', 'deepseek', 'groq', 'local_llm'] as $providerkey) {
        $providers[$providerkey] = get_string('provider_' . $providerkey, 'mod_knowledgebattle');
    }

    $settings->add(new admin_setting_configselect('mod_knowledgebattle/ai_provider', 
        get_string('ai_provider', 'mod_knowledgebattle'),
        get_string('ai_provider_desc', 'mod_knowledgebattle'), 
        'openrouter', $providers));

    // API Keys
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/openrouter_apikey', 
        get_string('openrouter_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/openai_apikey', 
        get_string('openai_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/gemini_apikey', 
        get_string('gemini_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/claude_apikey', 
        get_string('claude_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/deepseek_apikey', 
        get_string('deepseek_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/groq_apikey', 
        get_string('groq_apikey', 'mod_knowledgebattle'), '', ''));
    $settings->add(new admin_setting_configtext('mod_knowledgebattle/local_llm_baseurl',
        get_string('local_llm_baseurl', 'mod_knowledgebattle'),
        get_string('local_llm_baseurl_desc', 'mod_knowledgebattle'), 'http://localhost:11434', PARAM_URL));

    $settings->add(new admin_setting_configtext('mod_knowledgebattle/ai_model', 
        get_string('ai_model_default', 'mod_knowledgebattle'),
        '', 'gpt-3.5-turbo', PARAM_TEXT));

    // Test AI Connection button (rendered from a template, behaviour in AMD).
    $settings->add(new admin_setting_description(
        'mod_knowledgebattle/ai_test_connection',
        get_string('test_ai_connection', 'mod_knowledgebattle'),
        $OUTPUT->render_from_template('mod_knowledgebattle/settings_ai_test', [
            'buttonlabel' => get_string('test_ai_connection_btn', 'mod_knowledgebattle'),
            'description' => get_string('test_ai_connection_desc', 'mod_knowledgebattle'),
        ])
    ));

    $PAGE->requires->js_call_amd('mod_knowledgebattle/settings_ai_test', 'init');

    $settings->add(new admin_setting_configtext('mod_knowledgebattle/antiflood_limit',
        get_string('antiflood_limit', 'mod_knowledgebattle'),
        get_string('antiflood_limit_desc', 'mod_knowledgebattle'), 10, PARAM_INT));

}
