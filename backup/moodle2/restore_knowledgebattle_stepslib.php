<?php
/**
 * Restore steps lib.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_knowledgebattle_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('knowledgebattle', '/activity/knowledgebattle');
        $paths[] = new restore_path_element('knowledgebattle_question', '/activity/knowledgebattle/questions/question');
        
        if ($userinfo) {
            $paths[] = new restore_path_element('knowledgebattle_match', '/activity/knowledgebattle/matches/match');
            $paths[] = new restore_path_element('knowledgebattle_match_question', '/activity/knowledgebattle/matches/match/match_questions/match_question');
            $paths[] = new restore_path_element('knowledgebattle_turn', '/activity/knowledgebattle/matches/match/turns/turn');
            $paths[] = new restore_path_element('knowledgebattle_user_stat', '/activity/knowledgebattle/user_stats/user_stat');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_knowledgebattle($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        
        $newitemid = $DB->insert_record('knowledgebattle', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_knowledgebattle_question($data) {
        global $DB;

        $data = (object)$data;
        $data->knowledgebattleid = $this->get_new_parentid('knowledgebattle');
        
        $newitemid = $DB->insert_record('knowledgebattle_questions', $data);
        $this->set_mapping('knowledgebattle_question', $data->id, $newitemid);
    }

    protected function process_knowledgebattle_match($data) {
        global $DB;

        $data = (object)$data;
        $data->knowledgebattleid = $this->get_new_parentid('knowledgebattle');
        $data->player1_id = $this->get_mappingid('user', $data->player1_id);
        $data->player2_id = $this->get_mappingid('user', $data->player2_id);
        if (!empty($data->winner_id)) {
            $data->winner_id = $this->get_mappingid('user', $data->winner_id);
        }
        
        $newitemid = $DB->insert_record('knowledgebattle_matches', $data);
        $this->set_mapping('knowledgebattle_match', $data->id, $newitemid);
    }

    protected function process_knowledgebattle_match_question($data) {
        global $DB;

        $data = (object)$data;
        $data->match_id = $this->get_new_parentid('knowledgebattle_match');
        $data->question_id = $this->get_mappingid('knowledgebattle_question', $data->question_id);
        
        $DB->insert_record('knowledgebattle_match_quest', $data);
    }

    protected function process_knowledgebattle_turn($data) {
        global $DB;

        $data = (object)$data;
        $data->match_id = $this->get_new_parentid('knowledgebattle_match');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->question_id = $this->get_mappingid('knowledgebattle_question', $data->question_id);
        
        $DB->insert_record('knowledgebattle_turns', $data);
    }

    protected function process_knowledgebattle_user_stat($data) {
        global $DB;

        $data = (object)$data;
        $data->knowledgebattleid = $this->get_new_parentid('knowledgebattle');
        $data->userid = $this->get_mappingid('user', $data->userid);
        
        $DB->insert_record('knowledgebattle_user_stats', $data);
    }

    protected function after_execute() {
        // Nothing to do
    }
}
