<?php
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
 * Backup steps for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete backup structure of a Knowledge Battle activity.
 */
class backup_knowledgebattle_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $knowledgebattle = new backup_nested_element('knowledgebattle', ['id'], [
            'name', 'intro', 'introformat', 'grade', 'ai_provider', 'ai_model', 'content_scope',
            'topic_text', 'supply_mode', 'pool_size', 'questions_per_match',
            'time_per_question', 'wo_timeout_hours', 'win_points', 'draw_points',
            'loss_points', 'allow_negative_points', 'max_daily_battles', 'bot_enabled',
            'ranking_visibility', 'grade_criteria', 'timecreated', 'timemodified',
        ]);

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'question_text', 'options_json', 'correct_index', 'explanation', 'difficulty',
            'status', 'source_type', 'source_question_id', 'timecreated', 'timemodified',
        ]);

        $matches = new backup_nested_element('matches');
        $match = new backup_nested_element('match', ['id'], [
            'player1_id', 'player2_id', 'match_type', 'is_bot_match', 'status', 'winner_id',
            'p1_score', 'p2_score', 'p1_time_ms', 'p2_time_ms', 'timestarted',
            'timecompleted', 'timeexpire', 'timecreated', 'timemodified',
        ]);

        $matchquestions = new backup_nested_element('match_questions');
        $matchquestion = new backup_nested_element('match_question', ['id'], [
            'questionid', 'question_order',
        ]);

        $turns = new backup_nested_element('turns');
        $turn = new backup_nested_element('turn', ['id'], [
            'userid', 'questionid', 'selected_option', 'is_correct', 'response_time_ms', 'timecreated',
        ]);

        $userstats = new backup_nested_element('user_stats');
        $userstat = new backup_nested_element('user_stat', ['id'], [
            'userid', 'current_points', 'matches_played', 'wins', 'losses', 'draws',
            'current_streak', 'max_streak', 'last_battle_date', 'daily_battles_count',
            'timecreated', 'timemodified',
        ]);

        // Build the tree.
        $knowledgebattle->add_child($questions);
        $questions->add_child($question);

        $knowledgebattle->add_child($matches);
        $matches->add_child($match);

        $match->add_child($matchquestions);
        $matchquestions->add_child($matchquestion);

        $match->add_child($turns);
        $turns->add_child($turn);

        $knowledgebattle->add_child($userstats);
        $userstats->add_child($userstat);

        // Data sources.
        $knowledgebattle->set_source_table('knowledgebattle', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('knowledgebattle_questions', ['battleid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $match->set_source_table('knowledgebattle_matches', ['battleid' => backup::VAR_PARENTID]);
            $matchquestion->set_source_table('knowledgebattle_match_questions', ['matchid' => backup::VAR_PARENTID]);
            $turn->set_source_table('knowledgebattle_turns', ['matchid' => backup::VAR_PARENTID]);
            $userstat->set_source_table('knowledgebattle_user_stats', ['battleid' => backup::VAR_PARENTID]);
        }

        // Annotations.
        $match->annotate_ids('user', 'player1_id');
        $match->annotate_ids('user', 'player2_id');
        $match->annotate_ids('user', 'winner_id');
        $turn->annotate_ids('user', 'userid');
        $userstat->annotate_ids('user', 'userid');

        $knowledgebattle->annotate_files('mod_knowledgebattle', 'intro', null);

        return $this->prepare_activity_structure($knowledgebattle);
    }
}
