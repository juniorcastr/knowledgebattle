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
 * Restore steps for mod_knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete restore structure of a Knowledge Battle activity.
 */
class restore_knowledgebattle_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('knowledgebattle', '/activity/knowledgebattle');
        $paths[] = new restore_path_element('knowledgebattle_question', '/activity/knowledgebattle/questions/question');

        if ($userinfo) {
            $paths[] = new restore_path_element('knowledgebattle_match', '/activity/knowledgebattle/matches/match');
            $paths[] = new restore_path_element('knowledgebattle_match_question',
                '/activity/knowledgebattle/matches/match/match_questions/match_question');
            $paths[] = new restore_path_element('knowledgebattle_turn', '/activity/knowledgebattle/matches/match/turns/turn');
            $paths[] = new restore_path_element('knowledgebattle_user_stat', '/activity/knowledgebattle/user_stats/user_stat');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity instance.
     *
     * @param array $data
     */
    protected function process_knowledgebattle($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('knowledgebattle', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores a question.
     *
     * @param array $data
     */
    protected function process_knowledgebattle_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->battleid = $this->get_new_parentid('knowledgebattle');
        $data->source_question_id = 0;

        $newitemid = $DB->insert_record('knowledgebattle_questions', $data);
        $this->set_mapping('knowledgebattle_question', $oldid, $newitemid);
    }

    /**
     * Restores a match.
     *
     * @param array $data
     */
    protected function process_knowledgebattle_match($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->battleid = $this->get_new_parentid('knowledgebattle');
        $data->player1_id = $this->get_mappingid('user', $data->player1_id);
        $data->player2_id = !empty($data->player2_id) ? $this->get_mappingid('user', $data->player2_id) : 0;
        $data->winner_id = !empty($data->winner_id) ? $this->get_mappingid('user', $data->winner_id) : 0;

        $newitemid = $DB->insert_record('knowledgebattle_matches', $data);
        $this->set_mapping('knowledgebattle_match', $oldid, $newitemid);
    }

    /**
     * Restores a match question.
     *
     * @param array $data
     */
    protected function process_knowledgebattle_match_question($data) {
        global $DB;

        $data = (object)$data;
        $data->matchid = $this->get_new_parentid('knowledgebattle_match');
        $data->questionid = $this->get_mappingid('knowledgebattle_question', $data->questionid);
        if (empty($data->questionid)) {
            return;
        }

        $DB->insert_record('knowledgebattle_match_questions', $data);
    }

    /**
     * Restores a turn.
     *
     * @param array $data
     */
    protected function process_knowledgebattle_turn($data) {
        global $DB;

        $data = (object)$data;
        $data->matchid = $this->get_new_parentid('knowledgebattle_match');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->questionid = $this->get_mappingid('knowledgebattle_question', $data->questionid);
        if (empty($data->questionid)) {
            return;
        }

        $DB->insert_record('knowledgebattle_turns', $data);
    }

    /**
     * Restores user statistics.
     *
     * @param array $data
     */
    protected function process_knowledgebattle_user_stat($data) {
        global $DB;

        $data = (object)$data;
        $data->battleid = $this->get_new_parentid('knowledgebattle');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('knowledgebattle_user_stats', $data);
    }

    /**
     * Adds the intro files after the structure is restored.
     */
    protected function after_execute() {
        $this->add_related_files('mod_knowledgebattle', 'intro', null);
    }
}
