<?php
/**
 * Backup steps lib.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_knowledgebattle_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $knowledgebattle = new backup_nested_element('knowledgebattle', ['id'], [
            'name', 'intro', 'introformat', 'ai_provider', 'ai_model', 'content_scope',
            'topic_text', 'supply_mode', 'pool_size', 'questions_per_match',
            'time_per_question', 'wo_timeout_hours', 'win_points', 'draw_points',
            'loss_points', 'allow_negative_points', 'max_daily_battles', 'bot_enabled',
            'ranking_visibility', 'grade_criteria', 'timecreated', 'timemodified'
        ]);

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'question_text', 'options_json', 'correct_option_index', 'explanation',
            'status', 'timecreated', 'timemodified'
        ]);

        $matches = new backup_nested_element('matches');
        $match = new backup_nested_element('match', ['id'], [
            'player1_id', 'player2_id', 'match_type', 'status', 'winner_id',
            'timecreated', 'timeexpire', 'timecompleted'
        ]);

        $match_questions = new backup_nested_element('match_questions');
        $match_question = new backup_nested_element('match_question', ['id'], [
            'question_id', 'order_index'
        ]);

        $turns = new backup_nested_element('turns');
        $turn = new backup_nested_element('turn', ['id'], [
            'userid', 'question_id', 'answer_index', 'is_correct', 'timecreated'
        ]);

        $user_stats = new backup_nested_element('user_stats');
        $user_stat = new backup_nested_element('user_stat', ['id'], [
            'userid', 'matches_played', 'wins', 'losses', 'draws', 'current_points', 'streak'
        ]);

        $knowledgebattle->add_child($questions);
        $questions->add_child($question);

        $knowledgebattle->add_child($matches);
        $matches->add_child($match);

        $match->add_child($match_questions);
        $match_questions->add_child($match_question);

        $match->add_child($turns);
        $turns->add_child($turn);

        $knowledgebattle->add_child($user_stats);
        $user_stats->add_child($user_stat);

        $knowledgebattle->set_source_table('knowledgebattle', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('knowledgebattle_questions', ['knowledgebattleid' => backup::VAR_PARENTID]);
        $match->set_source_table('knowledgebattle_matches', ['knowledgebattleid' => backup::VAR_PARENTID]);
        $match_question->set_source_table('knowledgebattle_match_quest', ['match_id' => backup::VAR_PARENTID]);
        $turn->set_source_table('knowledgebattle_turns', ['match_id' => backup::VAR_PARENTID]);
        $user_stat->set_source_table('knowledgebattle_user_stats', ['knowledgebattleid' => backup::VAR_PARENTID]);

        $match->annotate_ids('user', 'player1_id');
        $match->annotate_ids('user', 'player2_id');
        $match->annotate_ids('user', 'winner_id');
        $turn->annotate_ids('user', 'userid');
        $user_stat->annotate_ids('user', 'userid');

        return $this->prepare_activity_structure($knowledgebattle);
    }
}
