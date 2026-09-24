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
        // Find grades and update
        $sql = "SELECT userid, current_points as rawgrade
                  FROM {knowledgebattle_user_stats}
                 WHERE battleid = ?";
        $params = [$knowledgebattle->id];

        if ($userid) {
            $sql .= " AND userid = ?";
            $params[] = $userid;
        }

        if ($rs = $DB->get_recordset_sql($sql, $params)) {
            foreach ($rs as $grade) {
                // Determine grade calculation based on criteria if needed
                // Currently returning current_points
                knowledgebattle_grade_item_update($knowledgebattle, $grade);
            }
            $rs->close();
        } else {
            knowledgebattle_grade_item_update($knowledgebattle);
        }
    }
}
