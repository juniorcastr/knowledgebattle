<?php
/**
 * Mod form for knowledgebattle.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_knowledgebattle_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // 1. General settings section (Name & Description).
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // 2. AI Configuration.
        $mform->addElement('header', 'ai_config_header', get_string('ai_config', 'mod_knowledgebattle'));

        $providers = [
            'openrouter' => 'OpenRouter',
            'openai'     => 'OpenAI',
            'gemini'     => 'Gemini',
            'claude'     => 'Claude',
            'deepseek'   => 'DeepSeek',
            'groq'       => 'Groq',
            'local_llm'  => 'Local LLM'
        ];
        $mform->addElement('select', 'ai_provider', get_string('ai_provider', 'mod_knowledgebattle'), $providers);
        $mform->setDefault('ai_provider', 'openrouter');

        $mform->addElement('text', 'ai_model', get_string('ai_model', 'mod_knowledgebattle'), ['size' => '50']);
        $mform->setType('ai_model', PARAM_TEXT);

        // 3. Content Configuration.
        $mform->addElement('header', 'content_config_header', get_string('content_config', 'mod_knowledgebattle'));

        $scopes = [
            1 => get_string('scope_topic', 'mod_knowledgebattle'),
            2 => get_string('scope_section', 'mod_knowledgebattle'),
            3 => get_string('scope_resource', 'mod_knowledgebattle'),
            4 => get_string('scope_bank', 'mod_knowledgebattle'),
        ];
        $mform->addElement('select', 'content_scope', get_string('content_scope', 'mod_knowledgebattle'), $scopes);
        $mform->setDefault('content_scope', 1);

        $mform->addElement('textarea', 'topic_text', get_string('topic_text', 'mod_knowledgebattle'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('topic_text', PARAM_TEXT);
        $mform->hideIf('topic_text', 'content_scope', 'noteq', 1);

        $modes = [
            1 => get_string('mode_pool', 'mod_knowledgebattle'),
            2 => get_string('mode_ondemand', 'mod_knowledgebattle')
        ];
        $mform->addElement('select', 'supply_mode', get_string('supply_mode', 'mod_knowledgebattle'), $modes);
        $mform->setDefault('supply_mode', 1);

        $mform->addElement('text', 'pool_size', get_string('pool_size', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('pool_size', PARAM_INT);
        $mform->setDefault('pool_size', 30);

        // 4. Battle Rules.
        $mform->addElement('header', 'battle_rules_header', get_string('battle_rules', 'mod_knowledgebattle'));

        $mform->addElement('text', 'questions_per_match', get_string('questions_per_match', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('questions_per_match', PARAM_INT);
        $mform->setDefault('questions_per_match', 5);

        $mform->addElement('text', 'time_per_question', get_string('time_per_question', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('time_per_question', PARAM_INT);
        $mform->setDefault('time_per_question', 30);

        $wo_options = [
            12 => '12 ' . get_string('hours', 'mod_knowledgebattle'),
            24 => '24 ' . get_string('hours', 'mod_knowledgebattle'),
            48 => '48 ' . get_string('hours', 'mod_knowledgebattle'),
            72 => '72 ' . get_string('hours', 'mod_knowledgebattle'),
        ];
        $mform->addElement('select', 'wo_timeout_hours', get_string('wo_timeout_hours', 'mod_knowledgebattle'), $wo_options);
        $mform->setDefault('wo_timeout_hours', 24);

        // 5. Points Configuration.
        $mform->addElement('header', 'points_header', get_string('points_config', 'mod_knowledgebattle'));

        $mform->addElement('text', 'win_points', get_string('win_points', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('win_points', PARAM_INT);
        $mform->setDefault('win_points', 100);

        $mform->addElement('text', 'draw_points', get_string('draw_points', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('draw_points', PARAM_INT);
        $mform->setDefault('draw_points', 30);

        $mform->addElement('text', 'loss_points', get_string('loss_points', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('loss_points', PARAM_INT);
        $mform->setDefault('loss_points', -20);

        $mform->addElement('advcheckbox', 'allow_negative_points', get_string('allow_negative_points', 'mod_knowledgebattle'));
        $mform->setDefault('allow_negative_points', 0);

        // 6. Limits.
        $mform->addElement('header', 'limits_header', get_string('limits_config', 'mod_knowledgebattle'));

        $mform->addElement('text', 'max_daily_battles', get_string('max_daily_battles', 'mod_knowledgebattle'), ['size' => '5']);
        $mform->setType('max_daily_battles', PARAM_INT);
        $mform->setDefault('max_daily_battles', 5);

        $mform->addElement('advcheckbox', 'bot_enabled', get_string('bot_enabled', 'mod_knowledgebattle'));
        $mform->setDefault('bot_enabled', 1);

        // 7. Display & Grading Criteria.
        $mform->addElement('header', 'display_header', get_string('display_config', 'mod_knowledgebattle'));

        $vis_options = [
            1 => get_string('ranking_all', 'mod_knowledgebattle'),
            2 => get_string('ranking_top10', 'mod_knowledgebattle'),
            3 => get_string('ranking_teacher', 'mod_knowledgebattle')
        ];
        $mform->addElement('select', 'ranking_visibility', get_string('ranking_visibility', 'mod_knowledgebattle'), $vis_options);
        $mform->setDefault('ranking_visibility', 1);

        $crit_options = [
            1 => get_string('crit_points', 'mod_knowledgebattle'),
            2 => get_string('crit_wins', 'mod_knowledgebattle'),
            3 => get_string('crit_participation', 'mod_knowledgebattle')
        ];
        $mform->addElement('select', 'grade_criteria', get_string('grade_criteria', 'mod_knowledgebattle'), $crit_options);
        $mform->setDefault('grade_criteria', 1);

        // 8. Standard Moodle elements (called strictly once).
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $group = [];
        $battlesenabled = 'completionbattlesenabled' . $suffix;
        $group[] = $mform->createElement('checkbox', $battlesenabled, '', get_string('completionbattles', 'mod_knowledgebattle'));
        $battlesval = 'completionbattles' . $suffix;
        $group[] = $mform->createElement('text', $battlesval, '', ['size' => 3]);
        $mform->setType($battlesval, PARAM_INT);
        $battlesgroup = 'completionbattlesgroup' . $suffix;
        $mform->addGroup($group, $battlesgroup, '', ' ', false);
        $mform->hideIf($battlesval, $battlesenabled, 'notchecked');

        $groupwins = [];
        $winsenabled = 'completionwinsenabled' . $suffix;
        $groupwins[] = $mform->createElement('checkbox', $winsenabled, '', get_string('completionwins', 'mod_knowledgebattle'));
        $winsval = 'completionwins' . $suffix;
        $groupwins[] = $mform->createElement('text', $winsval, '', ['size' => 3]);
        $mform->setType($winsval, PARAM_INT);
        $winsgroup = 'completionwinsgroup' . $suffix;
        $mform->addGroup($groupwins, $winsgroup, '', ' ', false);
        $mform->hideIf($winsval, $winsenabled, 'notchecked');

        return [$battlesgroup, $winsgroup];
    }

    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return (!empty($data['completionbattlesenabled' . $suffix]) && !empty($data['completionbattles' . $suffix]))
            || (!empty($data['completionwinsenabled' . $suffix]) && !empty($data['completionwins' . $suffix]));
    }

    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        $suffix = $this->get_suffix();
        if (!empty($this->current->customcompletionrules)) {
            $rules = $this->current->customcompletionrules;
            if (!empty($rules['completionbattles'])) {
                $default_values['completionbattlesenabled' . $suffix] = 1;
                $default_values['completionbattles' . $suffix] = $rules['completionbattles'];
            }
            if (!empty($rules['completionwins'])) {
                $default_values['completionwinsenabled' . $suffix] = 1;
                $default_values['completionwins' . $suffix] = $rules['completionwins'];
            }
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['content_scope'] == 1 && empty($data['topic_text'])) {
            $errors['topic_text'] = get_string('required');
        }

        if ($data['pool_size'] < 1) {
            $errors['pool_size'] = get_string('mustbepositive', 'mod_knowledgebattle');
        }
        if ($data['questions_per_match'] < 1) {
            $errors['questions_per_match'] = get_string('mustbepositive', 'mod_knowledgebattle');
        }
        if ($data['time_per_question'] < 5) {
            $errors['time_per_question'] = get_string('mustbepositive', 'mod_knowledgebattle');
        }

        return $errors;
    }
}
