<?php
/**
 * English language strings for Battle Quiz.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Battle Quiz';
$string['modulename'] = 'Battle Quiz';
$string['modulenameplural'] = 'Battle Quizzes';
$string['pluginadministration'] = 'Battle Quiz administration';

// Capabilities
$string['knowledgebattle:addinstance'] = 'Add a new Battle Quiz';
$string['knowledgebattle:view'] = 'View Battle Quiz';
$string['knowledgebattle:managequestions'] = 'Manage questions';
$string['knowledgebattle:viewallstats'] = 'View all statistics';
$string['knowledgebattle:challengebot'] = 'Challenge AI Master';
$string['knowledgebattle:generatequestions'] = 'Generate AI questions';

// Form sections and headers
$string['ai_config'] = 'AI Configuration';
$string['content_config'] = 'Content Configuration';
$string['battle_rules'] = 'Battle Rules';
$string['points_config'] = 'Score & Points Configuration';
$string['limits_config'] = 'Limits & Opponents';
$string['display_config'] = 'Display & Grading Settings';

// Form fields and settings
$string['ai_provider'] = 'AI Provider';
$string['ai_model'] = 'AI Model';
$string['content_scope'] = 'Content Scope';
$string['topic_text'] = 'Topic / Text';
$string['supply_mode'] = 'Supply Mode';
$string['pool_size'] = 'Pool Size';
$string['questions_per_match'] = 'Questions per Match';
$string['time_per_question'] = 'Time per Question (seconds)';
$string['wo_timeout_hours'] = 'W.O. Timeout (hours)';
$string['hours'] = 'hours';
$string['win_points'] = 'Win Points';
$string['draw_points'] = 'Draw Points';
$string['loss_points'] = 'Loss Points';
$string['allow_negative_points'] = 'Allow Negative Points';
$string['max_daily_battles'] = 'Max Daily Battles';
$string['bot_enabled'] = 'Enable AI Bot';
$string['ranking_visibility'] = 'Ranking Visibility';
$string['grade_criteria'] = 'Grade Criteria';
$string['mustbepositive'] = 'The value must be a positive number greater than zero.';
$string['player'] = 'Player';

// Content scope options
$string['scope_topic'] = 'Custom Topic';
$string['scope_section'] = 'Course Section';
$string['scope_resource'] = 'Specific Resource';
$string['scope_bank'] = 'Question Bank';
$string['scope_questionbank'] = 'Question Bank';

// Supply mode options
$string['mode_pool'] = 'Pre-generated Pool';
$string['mode_ondemand'] = 'On-Demand Generation';
$string['supply_pool'] = 'Pool-based';
$string['supply_ondemand'] = 'On-Demand Generation';

// Ranking visibility options
$string['ranking_all'] = 'All Users';
$string['ranking_top10'] = 'Top 10 Only';
$string['ranking_teacher'] = 'Teachers Only';
$string['ranking_teacheronly'] = 'Teachers Only';

// Grade criteria options
$string['crit_points'] = 'Total Points in Ranking';
$string['crit_wins'] = 'Number of Victories';
$string['crit_participation'] = 'Participation (Matches Played)';
$string['criteria_points'] = 'Total Points';
$string['criteria_wins'] = 'Number of Wins';
$string['criteria_participation'] = 'Participation (Matches Played)';

// Completion rules
$string['completionbattlesgroup'] = 'Battles played';
$string['completionwinsgroup'] = 'Victories earned';

// Match types
$string['match_direct'] = 'Direct Challenge';
$string['match_matchmaking'] = 'Matchmaking';
$string['match_bot'] = 'Bot Battle';

// Match statuses
$string['status_pending'] = 'Pending';
$string['status_waiting'] = 'Waiting for Opponent';
$string['status_completed'] = 'Completed';
$string['status_wo'] = 'W.O.';
$string['status_cancelled'] = 'Cancelled';

// Battle UI
$string['start_battle'] = 'Start Battle';
$string['challenge_player'] = 'Challenge Player';
$string['quick_match'] = 'Quick Match';
$string['challenge_bot'] = 'Challenge AI Master';
$string['waiting_opponent'] = 'Waiting for Opponent...';
$string['battle_result'] = 'Battle Result';
$string['you_won'] = 'You Won!';
$string['you_lost'] = 'You Lost!';
$string['draw'] = 'It\'s a Draw!';
$string['wo_win'] = 'W.O. Win!';
$string['view_leaderboard'] = 'View Leaderboard';
$string['request_rematch'] = 'Request Rematch';

// Events
$string['event_battle_started'] = 'Battle started';
$string['event_battle_completed'] = 'Battle completed';
$string['event_challenge_sent'] = 'Challenge sent';
$string['event_answer_submitted'] = 'Answer submitted';
$string['event_battle_expired'] = 'Battle expired (W.O.)';
$string['event_questions_generated'] = 'Questions generated';
$string['event_question_approved'] = 'Question approved';

// Errors
$string['error_no_questions'] = 'No questions available for this battle.';
$string['error_daily_limit'] = 'You have reached the maximum number of daily battles.';
$string['error_ai_failed'] = 'AI generation failed. Please try again later.';
$string['error_invalid_answer'] = 'Invalid answer submitted.';
$string['error_battle_expired'] = 'This battle has expired.';
$string['error_no_opponent'] = 'No suitable opponent found at the moment.';

// Misc
$string['manage_questions_tab'] = 'Manage Questions';
$string['question_pool'] = 'Question Pool';
$string['generate_questions_btn'] = 'Generate Questions';
$string['approve'] = 'Approve';
$string['discard'] = 'Discard';
$string['edit_question'] = 'Edit Question';
$string['question_count'] = 'Question Count: {$a}';
$string['leaderboard'] = 'Leaderboard';
$string['my_stats'] = 'My Stats';
$string['streak_label'] = 'Streak';
$string['points_label'] = 'Points';
$string['task_check_expired'] = 'Check for expired Battle Quiz matches';

$string['dailylimitreached'] = 'You have reached the maximum number of daily battles.';
$string['notenoughquestions'] = 'Not enough questions available to start the battle.';
$string['notyourmatch'] = 'You are not a participant in this match.';
$string['botdisabled'] = 'AI Bot battles are disabled for this activity.';
$string['alreadyanswered'] = 'You have already answered this question.';
$string['str_you_won'] = 'You Won!';
$string['str_you_lost'] = 'You Lost!';
$string['str_draw'] = 'It\'s a Draw!';
$string['report_title'] = 'Analytics & Performance Report';
$string['report_tab'] = 'Class Report';
$string['completionbattles'] = 'Require minimum battles';
$string['completionbattles_desc'] = 'Play at least {$a} battles';
$string['completionwins'] = 'Require minimum wins';
$string['completionwins_desc'] = 'Win at least {$a} battles';
$string['messageprovider:challenge'] = 'Challenge notifications';
$string['messageprovider:battleresult'] = 'Battle result notifications';
$string['messageprovider:wowarning'] = 'Battle expiry warnings';
$string['incoming_challenges'] = 'Incoming Challenges';
$string['waiting_matches'] = 'Waiting for Opponent';
$string['recent_matches'] = 'Recent Battles';
$string['accept_and_play'] = 'Accept & Play';
$string['view_result'] = 'View Result';
$string['error_already_waiting_opponent'] = 'You have already challenged this peer and are waiting for their turn.';
$string['error_match_already_completed'] = 'This battle is already completed.';
$string['error_user_already_finished'] = 'You have already answered all questions for this battle.';
$string['bot_name'] = 'AI Master';
$string['ai_provider_desc'] = 'Select the AI provider used for question generation.';
$string['openrouter_apikey'] = 'OpenRouter API Key';
$string['openai_apikey'] = 'OpenAI API Key';
$string['gemini_apikey'] = 'Google Gemini API Key';
$string['claude_apikey'] = 'Anthropic Claude API Key';
$string['deepseek_apikey'] = 'DeepSeek API Key';
$string['groq_apikey'] = 'Groq API Key';
$string['local_llm_baseurl'] = 'Local LLM Base URL';
$string['ai_model_default'] = 'Default AI Model';
$string['global_question_cache_ttl'] = 'Global Question Cache TTL (seconds)';
$string['antiflood_limit'] = 'Anti-Flood Limit (requests/hour)';

// Privacy API
$string['privacy:metadata:knowledgebattle_matches'] = 'Stores information about match sessions between players.';
$string['privacy:metadata:knowledgebattle_matches:player1_id'] = 'The ID of the challenger or first player in the match.';
$string['privacy:metadata:knowledgebattle_matches:player2_id'] = 'The ID of the opponent or second player in the match.';
$string['privacy:metadata:knowledgebattle_matches:winner_id'] = 'The ID of the winner of the match, or 0 in case of draw.';
$string['privacy:metadata:timecreated'] = 'The timestamp when this record was created.';
$string['privacy:metadata:timecompleted'] = 'The timestamp when the match was completed.';
$string['privacy:metadata:knowledgebattle_turns'] = 'Stores answers and turn information submitted by participants.';
$string['privacy:metadata:knowledgebattle_turns:userid'] = 'The ID of the user who submitted the answer.';
$string['privacy:metadata:knowledgebattle_turns:answer'] = 'The answer submitted by the user.';
$string['privacy:metadata:knowledgebattle_turns:is_correct'] = 'Whether the submitted answer was correct.';
$string['privacy:metadata:knowledgebattle_user_stats'] = 'Stores overall user game statistics, score, wins, and losses.';
$string['privacy:metadata:knowledgebattle_user_stats:userid'] = 'The ID of the user whose statistics are tracked.';
$string['privacy:metadata:knowledgebattle_user_stats:matches_played'] = 'The total number of matches played by the user.';
$string['privacy:metadata:knowledgebattle_user_stats:wins'] = 'The total number of matches won by the user.';
$string['privacy:metadata:knowledgebattle_user_stats:losses'] = 'The total number of matches lost by the user.';
$string['privacy:metadata:knowledgebattle_user_stats:draws'] = 'The total number of tied matches for the user.';
$string['privacy:metadata:knowledgebattle_user_stats:current_points'] = 'The total accumulated score or points of the user.';


