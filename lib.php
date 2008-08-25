<?php  // $Id$

/**
 * Library of functions and constants for module subcourse
 */

/**
 * Given an object containing all the necessary data, (defined by the form) 
 * this function will create a new instance and return the id number of the new 
 * instance.
 *
 * @param object $instance An object from the form
 * @return int The id of the newly inserted subcourse record
 */
function subcourse_add_instance($subcourse) {
    
    $subcourse->timecreated = time();
    $newid = insert_record("subcourse", $subcourse);

    // create grade_item but do not fetch grades - the context does not exist yet and we can't 
    // get users by capability
    subcourse_grades_update($subcourse->course, $newid, $subcourse->refcourse, $subcourse->name, true);
    return $newid;
}

/**
 * Given an object containing all the necessary data, (defined by the form) 
 * this function will update an existing instance with new data.
 *
 * @param object $instance An object from the form
 * @return boolean Success/Fail
 */
function subcourse_update_instance($subcourse) {

    $subcourse->timemodified = time();
    $subcourse->id = $subcourse->instance;

    subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse, $subcourse->name);
    $subcourse->timefetched = time();

    return update_record("subcourse", $subcourse);
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

    if (! $subcourse = get_record("subcourse", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("subcourse", "id", "$subcourse->id")) {
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
 * @return null
 * @todo Finish documenting this function
 */
function subcourse_user_outline($course, $user, $mod, $subcourse) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
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
 * @return boolean
 * @todo Finish documenting this function
 */
function subcourse_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
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
function subcourse_cron () {
    global $CFG;

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
    $subcourse = get_record("subcourse", "id", $subcourseid, '','', '','', 'id,refcourse');
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
 * @return mixed
 * @todo Finish documenting this function
 */
function subcourse_scale_used ($subcourseid,$scaleid) {
    $return = false;

    //$rec = get_record("subcourse","id","$subcourseid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Checks if scale is being used by any instance of subcourse.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any subcourse
 */
function subcourse_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('subcourse', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function subcourse_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function subcourse_uninstall() {
    return true;
}

//////////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the list of courses in which the user has permission to view the grade book
 *
 * Does not return the id of the current $COURSE and the site course (front page).
 * 
 * @param int $userid The ID of user for which we want to get the list of courses. Defaults to current $USER id.
 * @access public
 * @return array The list of course records
 */
function subcourse_available_courses($userid=NULL) {
    global $COURSE, $USER;

    $courses = array();   // to be returned
    if (empty($userid)) {
        $userid = $USER->id;
    }

    if ($mycourses = get_user_capability_course('moodle/grade:viewall', $userid, true, 
                                                    'fullname,shortname,idnumber,category,visible,sortorder','sortorder')) {
        foreach ($mycourses as $mycourse) {
            if ($mycourse->id != $COURSE->id && $mycourse->id != SITEID){
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
 * @param boolan $gradeitemonly If true, fetch only grade item info without grades
 * @return object Object containing grades array and gradeitem info
 */
function subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly=false) {
    global $CFG;

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
    foreach (array('itemname', 'idnumber', 'gradetype', 'grademax', 'grademin', 'scaleid', 'multfactor', 'plusfactor', 'deleted') as $property) {
        if (! empty($refgradeitem->$property)) {
            $return->$property = $refgradeitem->$property;
        } else {
            $return->$property = NULL;
        }
    }

    if (! $gradeitemonly) {
        // get grades
        $cm = get_coursemodule_from_instance("subcourse", $subcourseid);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $users = get_users_by_capability($context, 'mod/subcourse:begraded', 'u.id,u.lastname', 'u.lastname', '', '', '', '', false, true);
        foreach ($users as $user) {
            $grade = new grade_grade(array('itemid'=>$refgradeitem->id, 'userid'=>$user->id));
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
 * @param int $courseid     ID of referencing course (the course containing the instance of subcourse)
 * @param int $subcourseid  ID of subcourse instance 
 * @param int $refcourseid  ID of referenced course (the course to take grades from)
 * @param str $itemname     Set the itemname
 * @param boolan $gradeitemonly If true, fetch only grade item info without grades
 * @param boolean $reset Reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function subcourse_grades_update($courseid, $subcourseid, $refcourseid, $itemname=NULL, $gradeitemonly=false, $reset=false) {
    global $CFG;

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $refgrades = subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly);
    $params = array();

    foreach (array('idnumber', 'gradetype', 'grademax', 'grademin', 'scaleid', 'multfactor', 'plusfactor', 'deleted') as $property) {
        if (! empty ($refgrades->$property)) {
            $params[$property] = $refgrades->$property;
        }
    }
    if (!empty($itemname)) {
        $params['itemname'] = $itemname;
    }

    $grades = $refgrades->grades;

    if ($reset) {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/subcourse', $courseid, 'mod', 'subcourse', $subcourseid, 0, $grades, $params);
}


?>
