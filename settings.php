<?php
/**
 * Settings for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $providers = [
        'openrouter' => 'OpenRouter',
        'openai'     => 'OpenAI',
        'gemini'     => 'Gemini',
        'claude'     => 'Claude',
        'deepseek'   => 'DeepSeek',
        'groq'       => 'Groq',
        'local_llm'  => 'Local LLM'
    ];

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
    $settings->add(new admin_setting_configpasswordunmask('mod_knowledgebattle/local_llm_baseurl', 
        get_string('local_llm_baseurl', 'mod_knowledgebattle'), '', 'http://localhost:11434'));

    $settings->add(new admin_setting_configtext('mod_knowledgebattle/ai_model', 
        get_string('ai_model_default', 'mod_knowledgebattle'),
        '', 'gpt-3.5-turbo', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('mod_knowledgebattle/global_question_cache_ttl', 
        get_string('global_question_cache_ttl', 'mod_knowledgebattle'),
        '', 86400, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_knowledgebattle/antiflood_limit', 
        get_string('antiflood_limit', 'mod_knowledgebattle'),
        '', 50, PARAM_INT));

}
