define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        params: {},

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

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            $(document).on('click', '#btn-generate-questions', function() {
                var $btn = $(this);
                $btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Gerando com IA...');

                var battleId = self.getBattleId();
                if (!battleId) {
                    Notification.alert('Erro', 'Identificador da batalha não encontrado. Por favor, recarregue a página.', 'Recarregar', function() {
                        window.location.reload();
                    });
                    $btn.attr('disabled', false).html('<i class="fa fa-magic"></i> Gerar Questões');
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
                        message: response.generated_count + ' questões geradas com sucesso!',
                        type: 'success'
                    });
                    window.location.reload();
                }).fail(function(ex) {
                    Notification.exception(ex);
                    $btn.attr('disabled', false).html('<i class="fa fa-magic"></i> Gerar Questões');
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
                    message: 'Questão atualizada com sucesso!',
                    type: 'success'
                });
                window.location.reload();
            }).fail(Notification.exception);
        }
    };
});
