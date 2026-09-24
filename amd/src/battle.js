define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates'], function($, Ajax, Notification, Str, Templates) {
    var timerInterval = null;

    var BattleApp = {
        params: {},
        currentMatchId: null,
        currentQuestionId: null,
        currentQuestionNumber: 1,
        totalQuestions: 0,
        timeLimit: 30,
        questionStartTime: 0,
        isLastQuestion: false,
        lastMatchStatus: 1,
        lastMatchType: 1,
        lastOpponentId: null,

        init: function(params) {
            this.params = params;
            this.timeLimit = params.timePerQuestion || 30;
            this.bindEvents();

            if (params.activeMatchId && params.activeMatchId > 0) {
                this.currentMatchId = params.activeMatchId;
                this.currentQuestionNumber = 1;
                this.loadQuestion();
            }
        },

        bindEvents: function() {
            var self = this;

            $(document).on('click', '#btn-challenge-direct', function() {
                var opponentId = $('#direct-opponent').val();
                if (opponentId) {
                    self.startBattle(1, parseInt(opponentId, 10));
                }
            });

            $(document).on('click', '#btn-quick-match', function() {
                self.startBattle(2, null);
            });

            $(document).on('click', '#btn-challenge-bot', function() {
                self.startBattle(3, null);
            });

            $(document).on('click', '.option-btn', function() {
                $('.option-btn').attr('disabled', true);
                var optionIndex = parseInt($(this).data('index'), 10);
                self.submitAnswer(optionIndex, $(this));
            });

            $(document).on('click', '#btn-next-question', function() {
                if (self.isLastQuestion) {
                    self.showWaitingOrResults(self.lastMatchStatus);
                } else {
                    self.currentQuestionNumber++;
                    self.loadQuestion();
                }
            });

            $(document).on('click', '#btn-view-leaderboard', function() {
                self.showLeaderboard();
            });

            $(document).on('click', '#btn-back-lobby', function() {
                window.location.reload();
            });

            $(document).on('click', '#btn-rematch', function() {
                self.startBattle(self.lastMatchType, self.lastOpponentId);
            });

            $(document).on('change', '#direct-opponent', function() {
                if ($(this).val()) {
                    $('#btn-challenge-direct').removeAttr('disabled');
                } else {
                    $('#btn-challenge-direct').attr('disabled', true);
                }
            });
        },

        startBattle: function(matchType, opponentId) {
            var self = this;
            self.lastMatchType = matchType;
            self.lastOpponentId = opponentId;
            self.currentQuestionNumber = 1;
            self.isLastQuestion = false;

            var args = {
                battleid: self.params.battleid,
                match_type: matchType
            };
            if (opponentId) {
                args.opponent_id = opponentId;
            }

            Ajax.call([{
                methodname: 'mod_knowledgebattle_start_battle',
                args: args
            }])[0].then(function(response) {
                self.currentMatchId = response.matchid;
                self.totalQuestions = response.questions_count;
                self.timeLimit = response.time_per_question;
                self.loadQuestion();
            }).fail(Notification.exception);
        },

        loadQuestion: function() {
            var self = this;
            clearInterval(timerInterval);

            Ajax.call([{
                methodname: 'mod_knowledgebattle_get_question',
                args: {
                    matchid: self.currentMatchId,
                    question_number: self.currentQuestionNumber
                }
            }])[0].then(function(response) {
                self.currentQuestionId = response.questionid;
                self.totalQuestions = response.total_questions;
                self.timeLimit = response.time_limit;

                var letters = ['A', 'B', 'C', 'D', 'E'];
                var formattedOptions = [];
                if (response.options && response.options.length) {
                    for (var i = 0; i < response.options.length; i++) {
                        formattedOptions.push({
                            letter: letters[i] || (i + 1),
                            text: response.options[i],
                            index: i
                        });
                    }
                }

                var renderContext = {
                    current_question: response.question_number,
                    total_questions: response.total_questions,
                    time_limit: response.time_limit,
                    opponent_name: (self.lastMatchType === 3) ? 'Mestre IA' : 'Oponente',
                    question: {
                        id: response.questionid,
                        text: response.question_text,
                        options: formattedOptions
                    }
                };

                Templates.render('mod_knowledgebattle/battle_arena', renderContext).then(function(html, js) {
                    $('#knowledgebattle-content').html(html);
                    Templates.runTemplateJS(js);

                    // Update progress bar
                    var pct = ((self.currentQuestionNumber - 1) / self.totalQuestions) * 100;
                    $('#battle-progress').css('width', pct + '%');

                    self.questionStartTime = Date.now();
                    self.startTimer();
                });
            }).fail(Notification.exception);
        },

        startTimer: function() {
            var self = this;
            var timeLeft = self.timeLimit;
            $('#time-left').text(timeLeft);

            clearInterval(timerInterval);
            timerInterval = setInterval(function() {
                timeLeft--;
                $('#time-left').text(timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    self.autoSubmitTimeout();
                }
            }, 1000);
        },

        autoSubmitTimeout: function() {
            $('.option-btn').attr('disabled', true);
            this.submitAnswer(-1, null);
        },

        submitAnswer: function(optionIndex, $btnElement) {
            var self = this;
            clearInterval(timerInterval);

            var responseTimeMs = Date.now() - self.questionStartTime;
            if (responseTimeMs <= 0) {
                responseTimeMs = 1000;
            }

            Ajax.call([{
                methodname: 'mod_knowledgebattle_submit_answer',
                args: {
                    matchid: self.currentMatchId,
                    questionid: self.currentQuestionId,
                    selected_option: optionIndex,
                    response_time_ms: responseTimeMs
                }
            }])[0].then(function(response) {
                self.isLastQuestion = response.is_last_question;
                self.lastMatchStatus = response.match_status;

                $('#feedback-container').removeClass('d-none');
                if (response.is_correct) {
                    if ($btnElement) {
                        $btnElement.removeClass('btn-outline-primary').addClass('btn-success text-white');
                    }
                    $('#feedback-container').addClass('alert alert-success');
                    $('#feedback-title').html('<i class="fa fa-check"></i> Correto!');
                } else {
                    if ($btnElement) {
                        $btnElement.removeClass('btn-outline-primary').addClass('btn-danger text-white');
                    }
                    // Highlight correct option if available
                    $('.option-btn[data-index="' + response.correct_option_index + '"]')
                        .removeClass('btn-outline-primary')
                        .addClass('btn-success text-white');

                    $('#feedback-container').addClass('alert alert-danger');
                    $('#feedback-title').html('<i class="fa fa-times"></i> Incorreto!');
                }

                $('#feedback-explanation').text(response.explanation);

                var pct = (self.currentQuestionNumber / self.totalQuestions) * 100;
                $('#battle-progress').css('width', pct + '%');

                if (response.is_last_question) {
                    $('#btn-next-question').html('Ver Resultado <i class="fa fa-flag-checkered"></i>').removeClass('d-none');
                } else {
                    $('#btn-next-question').html('Próxima Questão <i class="fa fa-arrow-right"></i>').removeClass('d-none');
                }
            }).fail(Notification.exception);
        },

        showWaitingOrResults: function(status) {
            var self = this;
            if (status === 2) {
                // Waiting for opponent in direct invite or pool
                var waitingContext = {
                    opponent_name: (self.lastMatchType === 1 && self.lastOpponentId) ? 'seu colega' : null
                };
                Templates.render('mod_knowledgebattle/waiting_opponent', waitingContext).then(function(html, js) {
                    $('#knowledgebattle-content').html(html);
                    Templates.runTemplateJS(js);
                    setTimeout(function() { self.checkBattleStatus(); }, 8000);
                });
            } else {
                self.showResults(self.currentMatchId);
            }
        },

        checkBattleStatus: function() {
            var self = this;
            Ajax.call([{
                methodname: 'mod_knowledgebattle_get_battle_result',
                args: { matchid: self.currentMatchId }
            }])[0].then(function(response) {
                if (response.is_draw || response.winner_id > 0) {
                    self.showResults(self.currentMatchId, response);
                } else {
                    setTimeout(function() { self.checkBattleStatus(); }, 8000);
                }
            }).fail(function() {
                setTimeout(function() { self.checkBattleStatus(); }, 8000);
            });
        },

        showResults: function(matchid, preloadedResponse) {
            var self = this;
            var renderResults = function(data) {
                var context = {
                    is_win: data.user_is_winner,
                    is_loss: (!data.user_is_winner && !data.is_draw),
                    is_draw: data.is_draw,
                    str_you_won: 'VITÓRIA!',
                    str_you_lost: 'DERROTA!',
                    str_draw: 'EMPATE!',
                    points_earned: (data.points_earned >= 0 ? '+' : '') + data.points_earned + ' pontos',
                    p1_score: data.p1_score,
                    p2_score: data.p2_score,
                    p1_time: data.p1_time_ms,
                    p2_time: data.p2_time_ms,
                    opponent_name: data.p2_name,
                    questions_review: (data.questions || []).map(function(q, i) {
                        return {
                            id: i + 1,
                            num: i + 1,
                            question: q.question_text,
                            explanation: q.explanation
                        };
                    })
                };

                Templates.render('mod_knowledgebattle/battle_result', context).then(function(html, js) {
                    $('#knowledgebattle-content').html(html);
                    Templates.runTemplateJS(js);
                });
            };

            if (preloadedResponse) {
                renderResults(preloadedResponse);
            } else {
                Ajax.call([{
                    methodname: 'mod_knowledgebattle_get_battle_result',
                    args: { matchid: matchid }
                }])[0].then(renderResults).fail(Notification.exception);
            }
        },

        showLeaderboard: function() {
            var self = this;
            Ajax.call([{
                methodname: 'mod_knowledgebattle_get_leaderboard',
                args: { battleid: self.params.battleid, page: 0, perpage: 20 }
            }])[0].then(function(response) {
                var mapped = (response.rankings || []).map(function(r) {
                    return {
                        rank: r.rank,
                        name: r.fullname,
                        points: r.current_points,
                        wins: r.wins,
                        losses: r.losses,
                        streak: r.current_streak,
                        is_me: (r.userid === self.params.currentUserId)
                    };
                });

                var context = {
                    rankings: mapped,
                    top1: mapped[0] || null,
                    top2: mapped[1] || null,
                    top3: mapped[2] || null
                };

                Templates.render('mod_knowledgebattle/leaderboard', context).then(function(html, js) {
                    $('#knowledgebattle-content').html(html);
                    Templates.runTemplateJS(js);
                });
            }).fail(Notification.exception);
        }
    };

    return BattleApp;
});
