<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of functions, classes and constants for module subcourse
 *
 * @package     mod_subcourse
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/gradelib.php');

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function subcourse_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
        case FEATURE_GROUPS:            return true;
        case FEATURE_GROUPINGS:         return true;
        case FEATURE_GROUPMEMBERSONLY:  return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        default:                        return null;
    }
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will create a new instance and return the id number of the new
 * instance.
 *
 * @param stdClass $subcourse
 * @return int The id of the newly inserted subcourse record
 */
function subcourse_add_instance(stdClass $subcourse) {
    global $DB;

    $subcourse->timecreated = time();

    if (empty($subcourse->instantredirect)) {
        $subcourse->instantredirect = 0;
    }

    $newid = $DB->insert_record("subcourse", $subcourse);

    if (!empty($subcourse->refcourse)) {
        // create grade_item but do not fetch grades - the context does not exist yet and we can't
        // get users by capability
        subcourse_grades_update($subcourse->course, $newid, $subcourse->refcourse, $subcourse->name, true);
    }

    return $newid;
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will update an existing instance with new data.
 *
 * @param stdClass $subcourse
 * @return boolean success/failure
 */
function subcourse_update_instance(stdClass $subcourse) {
    global $DB;

    $cmid = $subcourse->coursemodule;

    $subcourse->timemodified = time();
    $subcourse->id = $subcourse->instance;

    if (!empty($subcourse->refcoursecurrent)) {
        unset($subcourse->refcourse);
    }

    if (empty($subcourse->instantredirect)) {
        $subcourse->instantredirect = 0;
    }

    $DB->update_record('subcourse', $subcourse);

    $subcourse = $DB->get_record('subcourse', array('id' => $subcourse->id));

    if (!empty($subcourse->refcourse)) {
        if (has_capability('mod/subcourse:fetchgrades', context_module::instance($cmid))) {
            subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse, $subcourse->name);
            subcourse_update_timefetched($subcourse->id);
        }
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean success/failure
 */
function subcourse_delete_instance($id) {
    global $DB;

    // Check the instance exists
    if (!$subcourse = $DB->get_record("subcourse", array("id" => $id))) {
        return false;
    }

    // Remove the instance record.
    $DB->delete_records("subcourse", array("id" => $subcourse->id));

    // Clean up the gradebook items.
    grade_update('mod/subcourse', $subcourse->course, 'mod', 'subcourse', $subcourse->id, 0, null, array('deleted' => true));

    return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of subcourse. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $subcourseid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function subcourse_get_participants($subcourseid) {
    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Reporting API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $subcourse
 * @return null
 * @todo Finish documenting this function
 */
function subcourse_user_outline($course, $user, $mod, $subcourse) {
    return true;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $subcourse
 * @return boolean
 * @todo Finish documenting this function
 */
function subcourse_user_complete($course, $user, $mod, $subcourse) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in subcourse activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param $course
 * @param $isteacher
 * @param $timestart
 * @return boolean
 * @todo Finish documenting this function
 */
function subcourse_print_recent_activity($course, $isteacher, $timestart) {
    return false; //  True if anything was printed, otherwise false
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Must return an array of grades for a given instance of this module,
 * indexed by user.  It also returns a maximum allowed grade.
 *
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $subcourseid ID of an instance of this module
 * @return stdClass|null object with an array of grades and with the maximum grade
 */
function subcourse_grades($subcourseid) {
    global $DB;
    $subcourse = $DB->get_record("subcourse", array("id" => $subcourseid), '', 'id, refcourse');
    if (empty($subcourse->refcourse)) {
        return null;
    }
    $refgrades = subcourse_fetch_refgrades($subcourse->id, $subcourse->refcourse);
    $return = new stdClass();
    $return->grades = $refgrades->grades;
    $return->maxgrade = $refgrades->grademax;

    return $return;
}

/**
 * Is a scale used by the given subcourse instance?
 *
 * The subcourse itself does not generate grades so we always return
 * false here in order not to block the scale removal.
 *
 * @param int $subcourseid id of an instance of this module
 * @param int $scaleid
 * @return bool
 */
function subcourse_scale_used($subcourseid, $scaleid) {
    return false;
}

/**
 * Is a scale used by some subcourse instance?
 *
 * The subcourse itself does not generate grades so we always return
 * false here in order not to block the scale removal.
 *
 * @param int $scaleid
 * @return boolean True if the scale is used by any subcourse
 */
function subcourse_scale_used_anywhere($scaleid) {
    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Internal                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the list of courses the grades can be taken from
 *
 * Returned are courses in which the user has permission to view the grade
 * book. Never returns the current course (as a course cannot be a subcourse of
 * itself) and the site course (the front page course). If the userid is not
 * passed, the current user is expected.
 *
 * @param int $userid Id of user for which we want to get the list of courses
 * @return array list of course records
 */
function subcourse_available_courses($userid = null) {
    global $COURSE, $USER, $DB;

    $courses = array(); // to be returned
    if (empty($userid)) {
        $userid = $USER->id;
    }
    $fields = 'fullname,shortname,idnumber,category,visible,sortorder';
    $mycourses = get_user_capability_course('moodle/grade:viewall', $userid,
                                            true, $fields, 'sortorder');

    if ($mycourses) {
        $ignorecourses = array($COURSE->id, SITEID);
        foreach ($mycourses as $mycourse) {
            if (in_array($mycourse->id, $ignorecourses)) {
                continue;
            }
            $courses[] = $mycourse;
        }
    }

    return $courses;
}

/**
 * Fetches grade_item info and grades from the referenced course
 *
 * Returned structure is
 *  object(
 *      ->grades = array[userid] of object(->userid ->rawgrade ->feedback ->feedbackformat)
 *      ->grademax
 *      ->grademin
 *      ->itemname
 *      ...
 *  )
 *
 * @access public
 * @param int $subcourseid ID of subcourse instance
 * @param int $refcourseid ID of referenced course
 * @param bool $gradeitemonly If true, fetch only grade item info without grades
 * @return stdClass containing grades array and gradeitem info
 */
function subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly = false) {

    if (empty($refcourseid)) {
        throw new coding_exception('Empty referenced course id');
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $return = new stdClass();
    $return->grades = array();

    $refgradeitem = grade_item::fetch_course_item($refcourseid);

    // get grade_item info
    foreach ($fetchedfields as $property) {
        if (!empty($refgradeitem->$property)) {
            $return->$property = $refgradeitem->$property;
        } else {
            $return->$property = null;
        }
    }

    // if the remote grade_item is non-global scale, do not fetch grades - they can't be used
    if (($refgradeitem->gradetype == GRADE_TYPE_SCALE) && (!subcourse_is_global_scale($refgradeitem->scaleid))) {
        $gradeitemonly = true;
        debugging(get_string('errlocalremotescale', 'subcourse'));
        $return->localremotescale = true;
    }

    if (!$gradeitemonly) {
        // get grades
        $cm = get_coursemodule_from_instance("subcourse", $subcourseid);
        $context = context_module::instance($cm->id);
        $users = get_users_by_capability($context, 'mod/subcourse:begraded', 'u.id,u.lastname',
                                         'u.lastname', '', '', '', '', false, true);
        foreach ($users as $user) {
            $grade = new grade_grade(array('itemid' => $refgradeitem->id, 'userid' => $user->id));
            $grade->grade_item =& $refgradeitem;
            $return->grades[$user->id] = new stdClass();
            $return->grades[$user->id]->userid = $user->id;
            $return->grades[$user->id]->rawgrade = $grade->finalgrade;
            $return->grades[$user->id]->feedback = $grade->feedback;
            $return->grades[$user->id]->feedbackformat = $grade->feedbackformat;
        }
    }

    return $return;
}

/**
 * Create or update grade item and grades for given subcourse
 *
 * @access public
 * @param int $courseid     ID of referencing course (the course containing the instance of
 * subcourse)
 * @param int $subcourseid  ID of subcourse instance
 * @param int $refcourseid  ID of referenced course (the course to take grades from)
 * @param str $itemname     Set the itemname
 * @param bool $gradeitemonly If true, fetch only grade item info without grades
 * @param bool $reset Reset grades in gradebook
 * @return int GRADE_UPDATE_OK etc
 */
function subcourse_grades_update($courseid, $subcourseid, $refcourseid, $itemname = null, $gradeitemonly = false, $reset = false) {
    global $DB;

    if (empty($refcourseid)) {
        return GRADE_UPDATE_FAILED;
    }

    if (!$DB->record_exists('course', array('id' => $refcourseid))) {
        return GRADE_UPDATE_FAILED;
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $refgrades = subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly);

    if (!empty($refgrades->localremotescale)) {
        // unable to fetch remote grades - local scale is used in the remote course
        return GRADE_UPDATE_FAILED;
    }

    $params = array();

    foreach ($fetchedfields as $property) {
        if (!empty ($refgrades->$property)) {
            $params[$property] = $refgrades->$property;
        }
    }
    if (!empty($itemname)) {
        $params['itemname'] = $itemname;
    }

    $grades = $refgrades->grades;

    if ($reset) {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/subcourse', $courseid, 'mod', 'subcourse', $subcourseid,
                        0, $grades, $params);
}

/**
 * Checks if a remote scale can be re-used, i.e. if it is global (standard, server wide) scale
 *
 * @param mixed $scaleid ID of the scale
 * @access public
 * @return boolean True if scale is global, false if not.
 */
function subcourse_is_global_scale($scaleid) {
    global $DB;

    if (!is_numeric($scaleid)) {
        throw new moodle_exception('errnonnumeric', 'subcourse');
    }

    if (!$DB->get_record('scale', array('id' => $scaleid, 'courseid' => 0), 'id')) {
        // no such scale with courseid ==0
        return false;
    } else {
        // found the global scale
        return true;
    }
}

/**
 * Updates the timefetched timestamp for given subcourses
 *
 * @param array|int $subcourseids ID of subcourse instance or array of IDs
 * @param mixed $time The timestamp, defaults to the current time
 * @access public
 * @return bool
 */
function subcourse_update_timefetched($subcourseids, $time = null) {
    global $DB;

    if (empty($subcourseids)) {
        return false;
    }
    if (is_numeric($subcourseids)) {
        $subcourseids = array($subcourseids);
    }
    if (!is_array($subcourseids)) {
        return false;
    }
    if (is_null($time)) {
        $time = time();
    }
    if (!is_numeric($time)) {
        return false;
    }
    list($sql, $params) = $DB->get_in_or_equal($subcourseids);
    $DB->set_field_select('subcourse', 'timefetched', $time, "id $sql", $params);
    return true;
}

/**
 * This will provide summary info about the user's grade in the subcourse below the link on
 * the course/view.php page
 *
 * @param cm_info $cm
 * @return void
 */
function mod_subcourse_cm_info_view(cm_info $cm) {
    global $USER;

    $currentgrade = grade_get_grades($cm->course, 'mod', 'subcourse', $cm->instance, $USER->id);

    if (!empty($currentgrade->items[0]->grades)) {
        $currentgrade = reset($currentgrade->items[0]->grades);
        if (isset($currentgrade->grade) and !($currentgrade->hidden)) {
            $strgrade = $currentgrade->str_grade;
            $html = html_writer::tag('div', get_string('currentgrade', 'subcourse', $strgrade), array('class' => 'contentafterlink'));
            $cm->set_after_link($html);
        }
    }
}

/**
 * The list of fields to copy from remote grade_item
 * @return array
 */
function subcourse_get_fetched_item_fields() {
    return array('gradetype', 'grademax', 'grademin', 'scaleid');
}
