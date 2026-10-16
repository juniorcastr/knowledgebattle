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
 * AMD module question_manager for mod_knowledgebattle.
 *
 * @module     mod_knowledgebattle/question_manager
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    return {
        params: {},
        strings: {},

        stringKeys: [
            'code_js_generating_ai', 'code_js_error', 'code_js_battleid_missing', 'code_js_reload',
            'code_js_generate_questions', 'code_js_questions_generated', 'code_js_question_updated'
        ],

        loadStrings: function() {
            var self = this;
            var requests = self.stringKeys.map(function(key) {
                var req = {key: key, component: 'mod_knowledgebattle'};
                if (key === 'code_js_questions_generated') {
                    req.param = '{COUNT}';
                }
                return req;
            });
            return Str.get_strings(requests).then(function(results) {
                self.stringKeys.forEach(function(key, i) {
                    self.strings[key] = results[i];
                });
                return true;
            });
        },

        str: function(key) {
            return this.strings[key] !== undefined ? this.strings[key] : key;
        },

        getBattleId: function() {
            var id = (this.params && this.params.battleid) ? this.params.battleid : null;
            if (!id) {
                id = $('#kb-battleid').val() ||
                     $('.knowledgebattle-question-manager').data('battleid') ||
                     $('[data-battleid]').data('battleid');
            }
            return parseInt(id, 10) || 0;
        },

        init: function(params) {
            if (typeof params === 'object' && params !== null && !Array.isArray(params)) {
                this.params = params;
            } else if (arguments.length > 1 || typeof params === 'number') {
                this.params = {
                    cmid: arguments[0],
                    battleid: arguments[1]
                };
            } else {
                this.params = {};
            }

            if (!this.params.battleid) {
                this.params.battleid = this.getBattleId();
            }

            this.loadStrings().catch(Notification.exception);
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            $(document).on('click', '#btn-generate-questions', function() {
                var $btn = $(this);
                $btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + self.str('code_js_generating_ai'));

                var battleId = self.getBattleId();
                if (!battleId) {
                    Notification.alert(self.str('code_js_error'), self.str('code_js_battleid_missing'), self.str('code_js_reload'), function() {
                        window.location.reload();
                    });
                    $btn.attr('disabled', false).html('<i class="fa fa-magic"></i> ' + self.str('code_js_generate_questions'));
                    return;
                }

                var count = parseInt($('#generate-questions-count').val() || 10, 10);
                if (isNaN(count) || count < 1) {
                    count = 10;
                }

                Ajax.call([{
                    methodname: 'mod_knowledgebattle_generate_questions',
                    args: {
                        battleid: battleId,
                        count: count
                    }
                }])[0].then(function(response) {
                    Notification.addNotification({
                        message: self.str('code_js_questions_generated').replace('{COUNT}', response.generated_count),
                        type: 'success'
                    });
                    window.location.reload();
                }).fail(function(ex) {
                    Notification.exception(ex);
                    $btn.attr('disabled', false).html('<i class="fa fa-magic"></i> ' + self.str('code_js_generate_questions'));
                });
            });

            $(document).on('click', '.btn-approve', function() {
                var qid = $(this).closest('tr').data('id');
                self.manageQuestion(qid, 'approve');
            });

            $(document).on('click', '.btn-discard', function() {
                var qid = $(this).closest('tr').data('id');
                self.manageQuestion(qid, 'discard');
            });

            $(document).on('click', '.btn-edit', function() {
                var $tr = $(this).closest('tr');
                var qid = $tr.data('id');
                $('#edit-question-id').val(qid);

                // Populate question text
                var qText = $tr.find('.q-text').text() || $tr.find('td strong').first().text();
                $('#edit-question-text').val(qText);

                // Populate explanation
                var explanation = $tr.find('.q-data-explanation').val() || '';
                $('#edit-explanation').val(explanation);

                // Populate correct radio
                var correctIndex = parseInt($tr.attr('data-correct') || $tr.data('correct'), 10) || 0;
                $('input[name="edit-correct"][value="' + correctIndex + '"]').prop('checked', true);

                // Populate options
                for (var i = 0; i < 4; i++) {
                    var optVal = $tr.find('.q-data-option-' + i).val() || '';
                    $('#edit-option-' + i).val(optVal);
                }

                $('#editQuestionModal').modal('show');
            });

            $(document).on('click', '#btn-save-question', function() {
                var qid = parseInt($('#edit-question-id').val(), 10);
                var text = $('#edit-question-text').val();
                var correct = parseInt($('input[name="edit-correct"]:checked').val() || 0, 10);
                var explanation = $('#edit-explanation').val();

                var options = [
                    $('#edit-option-0').val() || '',
                    $('#edit-option-1').val() || '',
                    $('#edit-option-2').val() || '',
                    $('#edit-option-3').val() || ''
                ];

                self.manageQuestion(qid, 'edit', {
                    question_text: text,
                    correct_index: correct,
                    options_json: JSON.stringify(options),
                    explanation: explanation
                });
                $('#editQuestionModal').modal('hide');
            });
        },

        manageQuestion: function(questionid, action, fields) {
            var self = this;
            var args = {
                questionid: parseInt(questionid, 10),
                action: action
            };

            if (fields) {
                if (fields.question_text !== undefined) args.question_text = fields.question_text;
                if (fields.options_json !== undefined) args.options_json = fields.options_json;
                if (fields.correct_index !== undefined) args.correct_index = fields.correct_index;
                if (fields.explanation !== undefined) args.explanation = fields.explanation;
            }

            Ajax.call([{
                methodname: 'mod_knowledgebattle_manage_question',
                args: args
            }])[0].then(function(response) {
                Notification.addNotification({
                    message: self.str('code_js_question_updated'),
                    type: 'success'
                });
                window.location.reload();
            }).fail(Notification.exception);
        }
    };
});
