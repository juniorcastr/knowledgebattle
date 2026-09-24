define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        params: {},

        init: function(params) {
            this.params = params;
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            $(document).on('click', '#btn-generate-questions', function() {
                var $btn = $(this);
                $btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Gerando com IA...');

                Ajax.call([{
                    methodname: 'mod_knowledgebattle_generate_questions',
                    args: {
                        battleid: self.params.battleid,
                        count: 10
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

                // Populate with existing text
                var qText = $tr.find('td strong').first().text();
                $('#edit-question-text').val(qText);

                $('#editQuestionModal').modal('show');
            });

            $(document).on('click', '#btn-save-question', function() {
                var qid = parseInt($('#edit-question-id').val(), 10);
                var text = $('#edit-question-text').val();
                var correct = parseInt($('input[name="edit-correct"]:checked').val() || 0, 10);
                var explanation = $('#edit-explanation').val();

                var options = [
                    $('#edit-option-0').val() || 'Opção A',
                    $('#edit-option-1').val() || 'Opção B',
                    $('#edit-option-2').val() || 'Opção C',
                    $('#edit-option-3').val() || 'Opção D'
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
                if (fields.question_text) args.question_text = fields.question_text;
                if (fields.options_json) args.options_json = fields.options_json;
                if (fields.correct_index !== undefined) args.correct_index = fields.correct_index;
                if (fields.explanation) args.explanation = fields.explanation;
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
