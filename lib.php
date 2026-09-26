<?php
/**
 * Knowledge Battle module functions.
 *
 * @package    mod_knowledgebattle
 * @copyright  2024 Junio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

/**
 * Indicates API features that the module supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function knowledgebattle_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE: return true;
        case FEATURE_BACKUP_MOODLE2: return true;
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        default: return null;
    }
}

/**
 * Add a new knowledgebattle instance.
 *
 * @param stdClass $data
 * @param mod_knowledgebattle_mod_form $mform
 * @return int new instance id
 */
function knowledgebattle_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    
    // Some defaults if not present
    if (!isset($data->bot_enabled)) {
        $data->bot_enabled = 1;
    }
    if (!isset($data->allow_negative_points)) {
        $data->allow_negative_points = 0;
    }

    $id = $DB->insert_record('knowledgebattle', $data);
    $data->id = $id;

    knowledgebattle_grade_item_update($data);

    return $id;
}

/**
 * Update a knowledgebattle instance.
 *
 * @param stdClass $data
 * @param mod_knowledgebattle_mod_form $mform
 * @return bool true
 */
function knowledgebattle_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Handle checkboxes
    $data->bot_enabled = !empty($data->bot_enabled) ? 1 : 0;
    $data->allow_negative_points = !empty($data->allow_negative_points) ? 1 : 0;

    $DB->update_record('knowledgebattle', $data);

    knowledgebattle_grade_item_update($data);

    return true;
}

/**
 * Delete a knowledgebattle instance.
 *
 * @param int $id
 * @return bool true
 */
function knowledgebattle_delete_instance($id) {
    global $DB;

    if (!$knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $id])) {
        return false;
    }

    // Delete all related records
    // 1. Matches and Turns and Questions
    $matches = $DB->get_records('knowledgebattle_matches', ['battleid' => $id]);
    if ($matches) {
        $matchids = array_keys($matches);
        list($insql, $inparams) = $DB->get_in_or_equal($matchids);
        
        $DB->delete_records_select('knowledgebattle_turns', "matchid $insql", $inparams);
        $DB->delete_records_select('knowledgebattle_match_questions', "matchid $insql", $inparams);
    }

    $DB->delete_records('knowledgebattle_matches', ['battleid' => $id]);
    $DB->delete_records('knowledgebattle_questions', ['battleid' => $id]);
    $DB->delete_records('knowledgebattle_user_stats', ['battleid' => $id]);

    // Delete grade item
    knowledgebattle_grade_item_delete($knowledgebattle);

    // Finally delete instance
    $DB->delete_records('knowledgebattle', ['id' => $id]);

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info info
 */
function knowledgebattle_get_coursemodule_info($coursemodule) {
    global $DB;

    if (!$knowledgebattle = $DB->get_record('knowledgebattle', ['id' => $coursemodule->instance], 'id, name, intro, introformat')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $knowledgebattle->name;
    
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('knowledgebattle', $knowledgebattle, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Update grade item for the given knowledgebattle.
 *
 * @param stdClass $knowledgebattle
 * @param mixed $grades
 * @return int result of grade_update()
 */
function knowledgebattle_grade_item_update($knowledgebattle, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = ['itemname' => $knowledgebattle->name, 'idnumber' => $knowledgebattle->cmidnumber ?? ''];

    if ($knowledgebattle->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $knowledgebattle->grade;
        $params['grademin']  = 0;
    } else if ($knowledgebattle->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$knowledgebattle->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/knowledgebattle', $knowledgebattle->course, 'mod', 'knowledgebattle', $knowledgebattle->id, 0, $grades, $params);
}

/**
 * Delete grade item for the given knowledgebattle.
 *
 * @param stdClass $knowledgebattle
 * @return int result of grade_update()
 */
function knowledgebattle_grade_item_delete($knowledgebattle) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/knowledgebattle', $knowledgebattle->course, 'mod', 'knowledgebattle', $knowledgebattle->id, 0, null, ['deleted' => 1]);
}

/**
 * Update grades for a given user or all users.
 *
 * @param stdClass $knowledgebattle
 * @param int $userid
 * @param bool $nullifnone
 */
function knowledgebattle_update_grades($knowledgebattle, $userid=0, $nullifnone=true) {
    global $DB;

    if ($knowledgebattle->grade == 0) {
        knowledgebattle_grade_item_update($knowledgebattle);
    } else {
        if (class_exists('\mod_knowledgebattle\grade_calculator')) {
            if ($userid) {
                $gradeval = \mod_knowledgebattle\grade_calculator::calculate_grade($knowledgebattle, $userid);
                if ($gradeval !== null) {
                    $grade = new \stdClass();
                    $grade->userid = $userid;
                    $grade->rawgrade = $gradeval;
                    knowledgebattle_grade_item_update($knowledgebattle, $grade);
                }
            } else {
                \mod_knowledgebattle\grade_calculator::update_all_grades($knowledgebattle);
            }
        } else {
            knowledgebattle_grade_item_update($knowledgebattle);
        }
    }
}

/**
 * Extends the global navigation.
 *
 * @param \global_navigation $nav
 * @param \stdClass $course
 * @param \context_module $context
 */
function knowledgebattle_extend_navigation_course($nav, $course, $context) {
    // Only extend if it's the right context
    if ($context->contextlevel == CONTEXT_MODULE) {
        $cm = get_coursemodule_from_id('knowledgebattle', $context->instanceid);
        if ($cm && has_capability('mod/knowledgebattle:view', $context)) {
            $node = $nav->get('module' . $cm->id);
            if ($node) {
                // Add leaderboard node
                $url = new \moodle_url('/mod/knowledgebattle/leaderboard.php', ['id' => $cm->id]);
                $node->add(get_string('leaderboard', 'mod_knowledgebattle'), $url, \navigation_node::TYPE_SETTING, null, 'leaderboard');
                
                // Add manage questions node
                if (has_capability('mod/knowledgebattle:managequestions', $context)) {
                    $url = new \moodle_url('/mod/knowledgebattle/questions.php', ['id' => $cm->id]);
                    $node->add(get_string('manage_questions_tab', 'mod_knowledgebattle'), $url, \navigation_node::TYPE_SETTING, null, 'managequestions');
                }
            }
        }
    }
}

/**
 * Obtains the automatic completion state for this module.
 *
 * @param \stdClass $course
 * @param \cm_info|\stdClass $cm
 * @param int $userid
 * @param bool $type
 * @return bool
 */
function knowledgebattle_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    
    // For now we assume if the user has played at least 1 match, they have completed the activity.
    // In a real scenario, this could be tied to a specific threshold in the activity settings.
    $stats = $DB->get_record('knowledgebattle_user_stats', ['battleid' => $cm->instance, 'userid' => $userid]);
    
    if ($stats && $stats->matches_played > 0) {
        return true;
    }
    
    return false;
}
