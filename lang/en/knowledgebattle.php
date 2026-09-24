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
$string['win_points'] = 'Win Points';
$string['draw_points'] = 'Draw Points';
$string['loss_points'] = 'Loss Points';
$string['allow_negative_points'] = 'Allow Negative Points';
$string['max_daily_battles'] = 'Max Daily Battles';
$string['bot_enabled'] = 'Enable AI Bot';
$string['ranking_visibility'] = 'Ranking Visibility';
$string['grade_criteria'] = 'Grade Criteria';

// Content scope options
$string['scope_topic'] = 'Custom Topic';
$string['scope_section'] = 'Course Section';
$string['scope_resource'] = 'Specific Resource';
$string['scope_questionbank'] = 'Question Bank';

// Supply mode options
$string['supply_pool'] = 'Pool-based';
$string['supply_ondemand'] = 'On-Demand Generation';

// Ranking visibility options
$string['ranking_all'] = 'All Users';
$string['ranking_top10'] = 'Top 10 Only';
$string['ranking_teacheronly'] = 'Teachers Only';

// Grade criteria options
$string['criteria_points'] = 'Total Points';
$string['criteria_wins'] = 'Number of Wins';
$string['criteria_participation'] = 'Participation (Matches Played)';

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
