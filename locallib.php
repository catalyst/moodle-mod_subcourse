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
 * Library providing functions that implement the module's features.
 *
 * @package     mod_subcourse
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/gradelib.php');

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

    $courses = array();

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $fields = 'fullname,shortname,idnumber,category,visible,sortorder';
    $mycourses = get_user_capability_course('moodle/grade:viewall', $userid, true, $fields, 'sortorder');

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
 * @param int $subcourseid ID of subcourse instance
 * @param int $refcourseid ID of referenced course
 * @param bool $gradeitemonly If true, fetch only grade item info without grades
 * @param int|array $userids If fetching grades, limit only to this user(s), defaults to all.
 * @return stdClass containing grades array and gradeitem info
 */
function subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly = false, $userids = []) {

    if (empty($refcourseid)) {
        throw new coding_exception('Empty referenced course id');
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $return = new stdClass();
    $return->grades = array();

    $refgradeitem = grade_item::fetch_course_item($refcourseid);

    // Get grade_item info.
    foreach ($fetchedfields as $property) {
        if (isset($refgradeitem->$property)) {
            $return->$property = $refgradeitem->$property;
        } else {
            $return->$property = null;
        }
    }

    // If the remote grade_item is non-global scale, do not fetch grades - they can't be used.
    if (($refgradeitem->gradetype == GRADE_TYPE_SCALE) && (!subcourse_is_global_scale($refgradeitem->scaleid))) {
        $gradeitemonly = true;
        debugging(get_string('errlocalremotescale', 'subcourse'));
        $return->localremotescale = true;
    }

    if (!$gradeitemonly) {
        // Get grades.

        if (!is_array($userids)) {
            $userids = array($userids);
        }

        $cm = get_coursemodule_from_instance("subcourse", $subcourseid);
        $context = context_module::instance($cm->id);

        $users = get_users_by_capability($context, 'mod/subcourse:begraded', 'u.id,u.lastname',
                                         'u.lastname', '', '', '', '', false, true);

        foreach ($users as $user) {
            if ($userids && !in_array($user->id, $userids)) {
                continue;
            }
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
 * @param int $courseid     ID of referencing course (the course containing the instance of
 * subcourse)
 * @param int $subcourseid  ID of subcourse instance
 * @param int $refcourseid  ID of referenced course (the course to take grades from)
 * @param str $itemname     Set the itemname
 * @param bool $gradeitemonly If true, fetch only grade item info without grades
 * @param bool $reset Reset grades in gradebook
 * @param int|array $userids If fetching grades, limit only to this user(s), defaults to all.
 * @return int GRADE_UPDATE_OK etc
 */
function subcourse_grades_update($courseid, $subcourseid, $refcourseid, $itemname = null,
        $gradeitemonly = false, $reset = false, $userids = []) {
    global $DB;

    if (empty($refcourseid)) {
        return GRADE_UPDATE_FAILED;
    }

    if (!$DB->record_exists('course', array('id' => $refcourseid))) {
        return GRADE_UPDATE_FAILED;
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $refgrades = subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly, $userids);

    if (!empty($refgrades->localremotescale)) {
        // Unable to fetch remote grades - local scale is used in the remote course.
        return GRADE_UPDATE_FAILED;
    }

    $params = array();

    foreach ($fetchedfields as $property) {
        if (isset($refgrades->$property)) {
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

    return grade_update('mod/subcourse', $courseid, 'mod', 'subcourse', $subcourseid, 0, $grades, $params);
}

/**
 * Checks if a remote scale can be re-used, i.e. if it is global (standard, server wide) scale
 *
 * @param mixed $scaleid ID of the scale
 * @return boolean True if scale is global, false if not.
 */
function subcourse_is_global_scale($scaleid) {
    global $DB;

    if (!is_numeric($scaleid)) {
        throw new moodle_exception('errnonnumeric', 'subcourse');
    }

    if (!$DB->get_record('scale', array('id' => $scaleid, 'courseid' => 0), 'id')) {
        // No such scale with courseid 0.
        return false;
    } else {
        // Found the global scale.
        return true;
    }
}

/**
 * Updates the timefetched timestamp for given subcourses
 *
 * @param array|int $subcourseids ID of subcourse instance or array of IDs
 * @param mixed $time The timestamp, defaults to the current time
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
 * The list of fields to copy from remote grade_item
 * @return array
 */
function subcourse_get_fetched_item_fields() {
    return array('gradetype', 'grademax', 'grademin', 'scaleid', 'hidden');
}
