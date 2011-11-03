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
 * Library of functions and constants for module subcourse
 */

require_once(dirname(__FILE__).'/exceptions.php');

/**
 * The list of fields to copy from remote grade_item
 * @return array
 */
function subcourse_get_fetched_item_fields() {
    return array('gradetype', 'grademax', 'grademin', 'scaleid');
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will create a new instance and return the id number of the new
 * instance.
 *
 * @param object $subcourse
 * @return int The id of the newly inserted subcourse record
 */
function subcourse_add_instance($subcourse) {
    global $DB;

    $subcourse->timecreated = time();
    $newid = $DB->insert_record("subcourse", $subcourse);

    // create grade_item but do not fetch grades - the context does not exist yet and we can't
    // get users by capability
    try {
        subcourse_grades_update($subcourse->course, $newid, $subcourse->refcourse,
                                $subcourse->name, true);
    } catch (subcourse_localremotescale_exception $e) {
        mtrace($e->getMessage());
    }
    return $newid;
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will update an existing instance with new data.
 *
 * @param object $subcourse
 * @return boolean Success/Fail
 */
function subcourse_update_instance($subcourse) {
    global $DB;

    $subcourse->timemodified = time();
    $subcourse->id = $subcourse->instance;

    try {
        subcourse_grades_update($subcourse->course, $subcourse->id,
                                $subcourse->refcourse, $subcourse->name);
    } catch (subcourse_localremotescale_exception $e) {
        mtrace($e->getMessage());
    }
    $subcourse->timefetched = time();

    return $DB->update_record("subcourse", $subcourse);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function subcourse_delete_instance($id) {
    global $DB;

    if (!$subcourse = $DB->get_record("subcourse", array("id" => $id))) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (!$DB->delete_records("subcourse", array("id" => $subcourse->id))) {
        $result = false;
    }

    return $result;
}

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
 * @uses $CFG
 * @param $course
 * @param $isteacher
 * @param $timestart
 * @return boolean
 * @todo Finish documenting this function
 */
function subcourse_print_recent_activity($course, $isteacher, $timestart) {
    return false; //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 */
function subcourse_cron() {
    global $DB;

    $subcourse_instances = $DB->get_records('subcourse', null, '', 'id, course, refcourse');
    if (empty($subcourse_instances)) {
        return true;
    }
    $updatedids = array();
    echo "Fetching grades from remote gradebooks...\n";
    foreach ($subcourse_instances as $subcourse) {
        $message = "Subcourse $subcourse->id: fetching grades from course $subcourse->refcourse ".
                   "to course $subcourse->course ... ";
        echo $message;
        try {
            subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
            $updatedids[] = $subcourse->id;
            echo "ok\n";
        } catch (subcourse_localremotescale_exception $e) {
            echo get_string($e->errorcode, 'subcourse')."\n";
        }
    }
    subcourse_update_timefetched($updatedids);

    return true;
}

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
 * @return mixed Null or object with an array of grades and with the maximum grade
 */
function subcourse_grades($subcourseid) {
    global $DB;
    $subcourse = $DB->get_record("subcourse", array("id" => $subcourseid), '', 'id, refcourse');
    $refgrades = subcourse_fetch_refgrades($subcourse->id, $subcourse->refcourse);
    $return = new stdClass();
    $return->grades = $refgrades->grades;
    $return->maxgrade = $refgrades->grademax;

    return $return;
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

/**
 * This function returns if a scale is being used by one subcourse
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $subcourseid ID of an instance of this module
 * @param int $scaleid
 * @return mixed
 * @todo Finish documenting this function
 */
function subcourse_scale_used($subcourseid, $scaleid) {
    $return = false;

    return $return;
}

/**
 * Checks if scale is being used by any instance of subcourse.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any subcourse
 */
function subcourse_scale_used_anywhere($scaleid) {
    global $DB;
    if ($scaleid and $DB->get_record('subcourse', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}


/**
 * Returns the list of courses in which the user has permission to view the grade book
 *
 * Does not return the id of the current $COURSE and the site course (front page).
 *
 * @param int $userid The ID of user for which we want to get the list of courses. Defaults to
 * current $USER id.
 * @access public
 * @return array The list of course records
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
    $existingsubcourses = $DB->get_records('subcourse', array('course' => $COURSE->id));

    if ($mycourses) {
        foreach ($mycourses as $mycourse) {
            if ($mycourse->id != $COURSE->id &&
                $mycourse->id != SITEID &&
                !array_key_exists($mycourse->id, $existingsubcourses)) {
                    foreach ($existingsubcourses as $existingsubcourse) {
                        if ($mycourse->id == $existingsubcourse->refcourse) {
                            continue 2;
                        }
                    }

                $courses[] = $mycourse;
            }
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
 * @param bool|\boolan $gradeitemonly If true, fetch only grade item info without grades
 * @return object Object containing grades array and gradeitem info
 */
function subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly = false) {
    global $CFG;

    $fetchedfields = subcourse_get_fetched_item_fields();

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $return = new stdClass();
    $return->grades = array();

    if (empty($refcourseid)) {
        return false;
    }
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
    if (($refgradeitem->gradetype == GRADE_TYPE_SCALE)
        && (!subcourse_is_global_scale($refgradeitem->scaleid))
    ) {

        $gradeitemonly = true;
        $return->localremotescale = true;
    }

    if (!$gradeitemonly) {
        // get grades
        $cm = get_coursemodule_from_instance("subcourse", $subcourseid);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $users = get_users_by_capability($context, 'mod/subcourse:begraded', 'u.id,u.lastname',
                                         'u.lastname', '', '', '', '', false, true);
        foreach ($users as $user) {
            $grade = new grade_grade(array('itemid' => $refgradeitem->id, 'userid' => $user->id));
            $grade->grade_item =& $refgradeitem;
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
 * @return int 0 if ok, error code otherwise
 */
function subcourse_grades_update($courseid, $subcourseid, $refcourseid, $itemname = null,
                                 $gradeitemonly = false, $reset = false) {
    global $CFG;
    $fetchedfields = subcourse_get_fetched_item_fields();

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $refgrades = subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly);
    if (!empty($refgrades->localremotescale)) {
        // unable to fetch remote grades - local scale is used in the remote course
        throw new subcourse_localremotescale_exception($subcourseid);
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
        throw new Exception('Non-numeric argument'); // TODO use moodle_exception in Moodle 2.0
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
 * @uses $CFG
 * @return bool
 */
function subcourse_update_timefetched($subcourseids, $time = null) {
    global $DB;

    if (is_numeric($subcourseids)) {
        $subcourseids = array($subcourseids);
    }
    if (!is_array($subcourseids)) {
        return false;
    }
    if (count($subcourseids) == 0) {
        return false;
    }
    if (empty($time)) {
        $time = time();
    }
    if (!is_numeric($time)) {
        return false;
    }
    $subcourseids = implode(',', $subcourseids);
    list($sql, $params) = $DB->get_in_or_equal($subcourseids);
    $DB->set_field_select('subcourse', 'timefetched', $time, "id $sql", $params);
    return true;
}
