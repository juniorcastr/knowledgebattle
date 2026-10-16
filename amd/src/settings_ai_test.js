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
 * AI provider helpers for the plugin settings page (connection test).
 *
 * @module     mod_knowledgebattle/settings_ai_test
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/templates', 'core/notification', 'core/str'], function(Ajax, Templates, Notification, Str) {
    'use strict';

    var PREFIX = '#id_s_mod_knowledgebattle_';

    /** Settings input that holds the credential of each provider. */
    var KEY_INPUTS = {
        openrouter: PREFIX + 'openrouter_apikey',
        openai: PREFIX + 'openai_apikey',
        gemini: PREFIX + 'gemini_apikey',
        claude: PREFIX + 'claude_apikey',
        deepseek: PREFIX + 'deepseek_apikey',
        groq: PREFIX + 'groq_apikey',
        local_llm: PREFIX + 'local_llm_baseurl'
    };

    var STRING_KEYS = [
        'test_ai_connection_testing',
        'test_ai_connection_success',
        'test_ai_connection_failed',
        'test_ai_connection_provider',
        'test_ai_connection_model',
        'test_ai_connection_latency',
        'test_ai_connection_reply'
    ].map(function(key) {
        return {key: key, component: 'mod_knowledgebattle'};
    });

    /**
     * Renders the result box.
     *
     * @param {HTMLElement} box Container element.
     * @param {Object} data Template context.
     * @returns {Promise}
     */
    var render = function(box, data) {
        return Templates.renderForPromise('mod_knowledgebattle/settings_ai_test_result', data)
            .then(function(rendered) {
                Templates.replaceNodeContents(box, rendered.html, rendered.js);
                box.classList.remove('d-none');
                return true;
            });
    };

    /**
     * Initialise the settings page behaviour.
     */
    var init = function() {
        var button = document.getElementById('kb-btn-test-ai');
        var box = document.getElementById('kb-test-ai-result');
        var providerSelect = document.querySelector(PREFIX + 'ai_provider');
        var modelInput = document.querySelector(PREFIX + 'ai_model');
        if (!button || !box || !providerSelect) {
            return;
        }

        // Move the focus to the credential of the newly selected provider.
        providerSelect.addEventListener('change', function() {
            var target = KEY_INPUTS[providerSelect.value];
            var input = target ? document.querySelector(target) : null;
            if (input) {
                input.focus();
            }
        });

        button.addEventListener('click', function(e) {
            e.preventDefault();

            var provider = providerSelect.value || 'openrouter';
            var keyInput = KEY_INPUTS[provider] ? document.querySelector(KEY_INPUTS[provider]) : null;
            var request = {
                provider: provider,
                model: modelInput ? modelInput.value.trim() : '',
                apikey: keyInput && provider !== 'local_llm' ? keyInput.value.trim() : '',
                baseurl: keyInput && provider === 'local_llm' ? keyInput.value.trim() : ''
            };
            var strings;
            var labels;

            button.disabled = true;
            Str.get_strings(STRING_KEYS).then(function(loaded) {
                strings = loaded;
                labels = {
                    providerlabel: strings[3],
                    modellabel: strings[4],
                    latencylabel: strings[5],
                    replylabel: strings[6]
                };
                return render(box, Object.assign({}, labels, {
                    testing: true,
                    title: strings[0],
                    provider: provider,
                    model: request.model
                }));
            }).then(function() {
                return Ajax.call([{methodname: 'mod_knowledgebattle_test_ai_connection', args: request}])[0];
            }).then(function(result) {
                return render(box, Object.assign({}, labels, {
                    success: result.success,
                    failed: !result.success,
                    title: result.success ? strings[1] : strings[2],
                    provider: result.provider,
                    model: result.model,
                    latency: result.latency,
                    reply: result.reply,
                    error: result.error
                }));
            }).catch(function(error) {
                box.classList.add('d-none');
                Notification.exception(error);
            }).then(function() {
                button.disabled = false;
                return true;
            });
        });
    };

    return {
        init: init
    };
});
