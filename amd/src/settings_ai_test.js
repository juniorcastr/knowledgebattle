// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define(['jquery'], function($) {
    'use strict';

    /**
     * Map of provider to its specific API key input selector.
     */
    var providerKeyInputs = {
        'openrouter': '#id_s_mod_knowledgebattle_openrouter_apikey',
        'openai': '#id_s_mod_knowledgebattle_openai_apikey',
        'gemini': '#id_s_mod_knowledgebattle_gemini_apikey',
        'claude': '#id_s_mod_knowledgebattle_claude_apikey',
        'deepseek': '#id_s_mod_knowledgebattle_deepseek_apikey',
        'groq': '#id_s_mod_knowledgebattle_groq_apikey',
        'local_llm': '#id_s_mod_knowledgebattle_local_llm_baseurl'
    };

    /**
     * Suggested default models per provider.
     */
    var providerDefaultModels = {
        'openrouter': 'google/gemini-2.5-flash-lite',
        'openai': 'gpt-4o-mini',
        'gemini': 'gemini-2.5-flash',
        'claude': 'claude-3-5-haiku-20241022',
        'deepseek': 'deepseek-chat',
        'groq': 'llama-3.3-70b-versatile',
        'local_llm': 'llama3.2'
    };

    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        return $('<div>').text(text).html();
    }

    function init() {
        var $providerSelect = $('#id_s_mod_knowledgebattle_ai_provider');
        var $modelInput = $('#id_s_mod_knowledgebattle_ai_model');
        var $btnTest = $('#kb-btn-test-ai');
        var $resultBox = $('#kb-test-ai-result');

        if (!$btnTest.length) {
            return;
        }

        // Highlight active API key when provider changes
        $providerSelect.on('change', function() {
            var selected = $(this).val();
            var targetInput = providerKeyInputs[selected];
            if (targetInput && $(targetInput).length) {
                $(targetInput).focus();
            }
        });

        $btnTest.on('click', function(e) {
            e.preventDefault();

            var provider = $providerSelect.val() || 'openrouter';
            var model = ($modelInput.val() || '').trim();
            var keySelector = providerKeyInputs[provider];
            var apiKey = keySelector ? ($(keySelector).val() || '').trim() : '';
            var baseUrl = '';
            if (provider === 'local_llm') {
                baseUrl = ($('#id_s_mod_knowledgebattle_local_llm_baseurl').val() || '').trim();
            }

            var originalBtnHtml = $btnTest.html();
            $btnTest.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1 me-1"></i> ' +
                (M.util.get_string ? M.util.get_string('test_ai_connection_testing', 'mod_knowledgebattle') : 'Testando conexão...'));

            $resultBox.show().html(
                '<div class="alert alert-info d-flex align-items-center py-2 mb-0">' +
                '<i class="fa fa-spinner fa-spin mr-2 me-2 fa-lg text-info"></i>' +
                '<div>' +
                '<strong>' + (M.util.get_string ? M.util.get_string('test_ai_connection_testing', 'mod_knowledgebattle') : 'Testando conexão...') + '</strong><br>' +
                '<small class="text-muted">Provedor: <strong>' + escapeHtml(provider) + '</strong> | Modelo: <strong>' + escapeHtml(model || providerDefaultModels[provider] || '') + '</strong></small>' +
                '</div>' +
                '</div>'
            );

            $.ajax({
                url: M.cfg.wwwroot + '/mod/knowledgebattle/ajax_test_connection.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    sesskey: M.cfg.sesskey,
                    provider: provider,
                    model: model,
                    apikey: apiKey,
                    baseurl: baseUrl
                },
                timeout: 20000
            }).done(function(data) {
                $btnTest.prop('disabled', false).html(originalBtnHtml);

                if (data.success) {
                    var latencyBadge = '<span class="badge badge-info bg-info text-white ml-1 ms-1">' + data.latency + 'ms</span>';
                    var providerBadge = '<span class="badge badge-primary bg-primary text-white ml-1 ms-1">' + escapeHtml(data.provider || provider) + '</span>';
                    var modelBadge = '<span class="badge badge-secondary bg-secondary text-white ml-1 ms-1">' + escapeHtml(data.model || model) + '</span>';
                    var replyText = data.reply ? '<div class="mt-1 small text-muted font-italic">Resposta: "' + escapeHtml(data.reply) + '"</div>' : '';

                    $resultBox.html(
                        '<div class="alert alert-success d-flex align-items-start py-2 mb-0 shadow-sm" style="border-left: 4px solid #198754;">' +
                        '<i class="fa fa-check-circle fa-2x text-success mr-2 me-2 mt-1"></i>' +
                        '<div class="flex-grow-1">' +
                        '<strong>' + (M.util.get_string ? M.util.get_string('test_ai_connection_success', 'mod_knowledgebattle') : 'Conexão realizada com sucesso!') + '</strong>' +
                        '<div class="mt-1 small">' +
                        'Provedor: ' + providerBadge + ' ' +
                        'Modelo: ' + modelBadge + ' ' +
                        'Latência: ' + latencyBadge +
                        '</div>' +
                        replyText +
                        '</div>' +
                        '</div>'
                    );
                } else {
                    var errorMsg = data.error || (M.util.get_string ? M.util.get_string('test_ai_connection_failed', 'mod_knowledgebattle') : 'Falha na conexão com a IA');
                    var latencyInfo = data.latency ? ' (' + data.latency + 'ms)' : '';

                    $resultBox.html(
                        '<div class="alert alert-danger d-flex align-items-start py-2 mb-0 shadow-sm" style="border-left: 4px solid #dc3545;">' +
                        '<i class="fa fa-exclamation-triangle fa-2x text-danger mr-2 me-2 mt-1"></i>' +
                        '<div class="flex-grow-1">' +
                        '<strong>' + (M.util.get_string ? M.util.get_string('test_ai_connection_failed', 'mod_knowledgebattle') : 'Falha na conexão') + '</strong>' +
                        '<div class="mt-1 text-danger font-weight-bold">' + escapeHtml(errorMsg) + latencyInfo + '</div>' +
                        '<small class="text-muted d-block mt-1">Provedor: ' + escapeHtml(data.provider || provider) + ' | Modelo: ' + escapeHtml(data.model || model) + '</small>' +
                        '</div>' +
                        '</div>'
                    );
                }
            }).fail(function(xhr, status, error) {
                $btnTest.prop('disabled', false).html(originalBtnHtml);
                var errDetail = error || status || 'Erro de rede ou timeout';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errDetail = xhr.responseJSON.error;
                }

                $resultBox.html(
                    '<div class="alert alert-danger d-flex align-items-center py-2 mb-0" style="border-left: 4px solid #dc3545;">' +
                    '<i class="fa fa-times-circle fa-2x text-danger mr-2 me-2"></i>' +
                    '<div>' +
                    '<strong>Erro na requisição AJAX:</strong> ' + escapeHtml(errDetail) + '<br>' +
                    '<small class="text-muted">Verifique se sua sessão no Moodle ainda está ativa e tente novamente.</small>' +
                    '</div>' +
                    '</div>'
                );
            });
        });
    }

    return {
        init: init
    };
});
