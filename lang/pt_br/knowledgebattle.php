<?php
/**
 * Brazilian Portuguese language strings for Battle Quiz.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Batalha de Conhecimento';
$string['modulename'] = 'Batalha de Conhecimento';
$string['modulenameplural'] = 'Batalhas de Conhecimento';
$string['pluginadministration'] = 'Administração da Batalha de Conhecimento';

// Capabilities
$string['knowledgebattle:addinstance'] = 'Adicionar nova Batalha de Conhecimento';
$string['knowledgebattle:view'] = 'Ver Batalha de Conhecimento';
$string['knowledgebattle:managequestions'] = 'Gerenciar questões';
$string['knowledgebattle:viewallstats'] = 'Ver todas as estatísticas';
$string['knowledgebattle:challengebot'] = 'Desafiar Mestre IA';
$string['knowledgebattle:generatequestions'] = 'Gerar questões com IA';

// Form sections and headers
$string['ai_config'] = 'Configuração da Inteligência Artificial';
$string['content_config'] = 'Configuração do Conteúdo';
$string['battle_rules'] = 'Regras da Batalha';
$string['points_config'] = 'Configuração de Pontuação';
$string['limits_config'] = 'Limites & Oponentes';
$string['display_config'] = 'Exibição & Critérios de Avaliação';

// Form fields and settings
$string['ai_provider'] = 'Provedor de IA';
$string['ai_model'] = 'Modelo de IA';
$string['content_scope'] = 'Escopo de Conteúdo';
$string['topic_text'] = 'Tópico / Texto';
$string['supply_mode'] = 'Modo de Fornecimento';
$string['pool_size'] = 'Tamanho do Banco';
$string['questions_per_match'] = 'Questões por Partida';
$string['time_per_question'] = 'Tempo por Questão (segundos)';
$string['wo_timeout_hours'] = 'Tempo Limite para W.O. (horas)';
$string['hours'] = 'horas';
$string['win_points'] = 'Pontos por Vitória';
$string['draw_points'] = 'Pontos por Empate';
$string['loss_points'] = 'Pontos por Derrota';
$string['allow_negative_points'] = 'Permitir Pontos Negativos';
$string['max_daily_battles'] = 'Máximo de Batalhas Diárias';
$string['bot_enabled'] = 'Habilitar Bot IA';
$string['ranking_visibility'] = 'Visibilidade do Ranking';
$string['grade_criteria'] = 'Critério de Avaliação';
$string['mustbepositive'] = 'O valor deve ser um número positivo maior que zero.';
$string['player'] = 'Jogador';

// Content scope options
$string['scope_topic'] = 'Tópico Personalizado';
$string['scope_section'] = 'Seção do Curso';
$string['scope_resource'] = 'Recurso Específico';
$string['scope_bank'] = 'Banco de Questões';
$string['scope_questionbank'] = 'Banco de Questões';

// Supply mode options
$string['mode_pool'] = 'Pool Pré-gerado';
$string['mode_ondemand'] = 'Geração Sob Demanda';
$string['supply_pool'] = 'Baseado em Pool/Banco';
$string['supply_ondemand'] = 'Geração Sob Demanda';

// Ranking visibility options
$string['ranking_all'] = 'Todos os Usuários';
$string['ranking_top10'] = 'Apenas Top 10';
$string['ranking_teacher'] = 'Apenas Professores';
$string['ranking_teacheronly'] = 'Apenas Professores';

// Grade criteria options
$string['crit_points'] = 'Pontuação no Ranking';
$string['crit_wins'] = 'Número de Vitórias';
$string['crit_participation'] = 'Participação (Partidas Jogadas)';
$string['criteria_points'] = 'Pontos Totais';
$string['criteria_wins'] = 'Número de Vitórias';
$string['criteria_participation'] = 'Participação (Partidas Jogadas)';

// Completion rules
$string['completionbattlesgroup'] = 'Batalhas disputadas';
$string['completionwinsgroup'] = 'Vitórias obtidas';

// Match types
$string['match_direct'] = 'Desafio Direto';
$string['match_matchmaking'] = 'Partida Ranqueada';
$string['match_bot'] = 'Batalha contra Bot';

// Match statuses
$string['status_pending'] = 'Pendente';
$string['status_waiting'] = 'Aguardando Oponente';
$string['status_completed'] = 'Concluída';
$string['status_wo'] = 'W.O.';
$string['status_cancelled'] = 'Cancelada';

// Battle UI
$string['start_battle'] = 'Iniciar Batalha';
$string['challenge_player'] = 'Desafiar';
$string['quick_match'] = 'Batalha Rápida';
$string['challenge_bot'] = 'Desafiar Mestre IA';
$string['waiting_opponent'] = 'Aguardando Oponente...';
$string['battle_result'] = 'Resultado da Batalha';
$string['you_won'] = 'Você Venceu!';
$string['you_lost'] = 'Você Perdeu!';
$string['draw'] = 'Empate!';
$string['wo_win'] = 'Vitória por W.O.!';
$string['view_leaderboard'] = 'Ver Ranking';
$string['request_rematch'] = 'Solicitar Revanche';

// Events
$string['event_battle_started'] = 'Batalha iniciada';
$string['event_battle_completed'] = 'Batalha concluída';
$string['event_challenge_sent'] = 'Desafio enviado';
$string['event_answer_submitted'] = 'Resposta enviada';
$string['event_battle_expired'] = 'Batalha expirada (W.O.)';
$string['event_questions_generated'] = 'Questões geradas';
$string['event_question_approved'] = 'Questão aprovada';

// Errors
$string['error_no_questions'] = 'Não há questões disponíveis para esta batalha.';
$string['error_daily_limit'] = 'Você atingiu o limite máximo de batalhas diárias.';
$string['error_ai_failed'] = 'A geração por IA falhou. Tente novamente mais tarde.';
$string['error_invalid_answer'] = 'Resposta enviada inválida.';
$string['error_battle_expired'] = 'Esta batalha expirou.';
$string['error_no_opponent'] = 'Nenhum oponente adequado encontrado no momento.';

// Misc
$string['manage_questions_tab'] = 'Gerenciar Questões';
$string['question_pool'] = 'Pool/Banco de Questões';
$string['generate_questions_btn'] = 'Gerar Questões';
$string['approve'] = 'Aprovar';
$string['discard'] = 'Descartar';
$string['edit_question'] = 'Editar Questão';
$string['question_count'] = 'Contagem de Questões: {$a}';
$string['leaderboard'] = 'Classificação';
$string['my_stats'] = 'Minhas Estatísticas';
$string['streak_label'] = 'Ofensiva';
$string['points_label'] = 'Pontos';
$string['task_check_expired'] = 'Verificar partidas expiradas da Batalha de Conhecimento';

$string['dailylimitreached'] = 'Você atingiu o limite máximo de batalhas diárias.';
$string['notenoughquestions'] = 'Não há questões suficientes para iniciar a batalha.';
$string['notyourmatch'] = 'Você não é um participante desta partida.';
$string['botdisabled'] = 'Batalhas contra a IA estão desativadas nesta atividade.';
$string['alreadyanswered'] = 'Você já respondeu a esta questão.';
$string['str_you_won'] = 'Você Venceu!';
$string['str_you_lost'] = 'Você Perdeu!';
$string['str_draw'] = 'Empate!';
$string['report_title'] = 'Relatório Analítico & Desempenho';
$string['report_tab'] = 'Relatório da Turma';
$string['completionbattles'] = 'Exigir número mínimo de batalhas';
$string['completionbattles_desc'] = 'Disputar pelo menos {$a} batalhas';
$string['completionwins'] = 'Exigir número mínimo de vitórias';
$string['completionwins_desc'] = 'Vencer pelo menos {$a} batalhas';
$string['messageprovider:challenge'] = 'Notificações de desafio';
$string['messageprovider:battleresult'] = 'Notificações de resultado de batalha';
$string['messageprovider:wowarning'] = 'Avisos de expiração de batalha (W.O.)';
$string['incoming_challenges'] = 'Desafios Recebidos';
$string['waiting_matches'] = 'Aguardando Oponente';
$string['recent_matches'] = 'Batalhas Recentes';
$string['accept_and_play'] = 'Aceitar e Jogar';
$string['view_result'] = 'Ver Resultado';
$string['error_already_waiting_opponent'] = 'Você já enviou um desafio a este colega e está aguardando a resposta dele.';
$string['error_match_already_completed'] = 'Esta batalha já foi concluída.';
$string['error_user_already_finished'] = 'Você já respondeu a todas as questões desta batalha.';
$string['bot_name'] = 'Mestre IA';
$string['ai_provider_desc'] = 'Selecione o provedor de IA utilizado para gerar questões.';
$string['openrouter_apikey'] = 'Chave de API OpenRouter';
$string['openai_apikey'] = 'Chave de API OpenAI';
$string['gemini_apikey'] = 'Chave de API Google Gemini';
$string['claude_apikey'] = 'Chave de API Anthropic Claude';
$string['deepseek_apikey'] = 'Chave de API DeepSeek';
$string['groq_apikey'] = 'Chave de API Groq';
$string['local_llm_baseurl'] = 'URL Base do LLM Local';
$string['ai_model_default'] = 'Modelo de IA Padrão';
$string['global_question_cache_ttl'] = 'TTL do Cache Global de Questões (segundos)';
$string['antiflood_limit'] = 'Limite Anti-Flood (chamadas/hora)';

// Help Guide
$string['help_guide_btn'] = 'Ajuda & Guia de Uso';
$string['help_guide_title'] = 'Guia de Configuração e Uso Passo a Passo';
$string['help_guide_desc'] = 'Aprenda a configurar chaves de IA, criar atividades e gerenciar batalhas pedagógicas.';

// Privacy API
$string['privacy:metadata:knowledgebattle_matches'] = 'Armazena informações sobre as sessões de partida entre jogadores.';
$string['privacy:metadata:knowledgebattle_matches:player1_id'] = 'O ID do desafiante ou primeiro jogador da partida.';
$string['privacy:metadata:knowledgebattle_matches:player2_id'] = 'O ID do oponente ou segundo jogador da partida.';
$string['privacy:metadata:knowledgebattle_matches:winner_id'] = 'O ID do vencedor da partida, ou 0 em caso de empate.';
$string['privacy:metadata:timecreated'] = 'O timestamp de quando o registro foi criado.';
$string['privacy:metadata:timecompleted'] = 'O timestamp de quando a partida foi concluída.';
$string['privacy:metadata:knowledgebattle_turns'] = 'Armazena respostas e informações de turnos submetidos pelos participantes.';
$string['privacy:metadata:knowledgebattle_turns:userid'] = 'O ID do usuário que respondeu à questão.';
$string['privacy:metadata:knowledgebattle_turns:answer'] = 'A resposta selecionada pelo usuário.';
$string['privacy:metadata:knowledgebattle_turns:is_correct'] = 'Indica se a resposta enviada foi correta.';
$string['privacy:metadata:knowledgebattle_user_stats'] = 'Armazena estatísticas gerais do usuário, pontuação, vitórias e derrotas.';
$string['privacy:metadata:knowledgebattle_user_stats:userid'] = 'O ID do usuário associado a essas estatísticas.';
$string['privacy:metadata:knowledgebattle_user_stats:matches_played'] = 'Total de partidas jogadas pelo usuário.';
$string['privacy:metadata:knowledgebattle_user_stats:wins'] = 'Total de partidas vencidas pelo usuário.';
$string['privacy:metadata:knowledgebattle_user_stats:losses'] = 'Total de partidas perdidas pelo usuário.';
$string['privacy:metadata:knowledgebattle_user_stats:draws'] = 'Total de partidas empatadas pelo usuário.';
$string['privacy:metadata:knowledgebattle_user_stats:current_points'] = 'Pontuação total acumulada pelo usuário.';


