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
    global $COURSE, $USER;

    $courses = [];

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $fields = 'fullname,shortname,idnumber,category,visible,sortorder';
    $mycourses = get_user_capability_course('moodle/grade:viewall', $userid, true, $fields, 'sortorder');

    if ($mycourses) {
        $ignorecourses = [$COURSE->id, SITEID];
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
 *      ->grades = array[userid] of object(->userid ->rawgrade ->feedback ->feedbackformat ->hidden)
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
 * @param bool $fetchpercentage Re-calculate the grade value so that the displayed percentage matches the original.
 * @return stdClass containing grades array and gradeitem info
 */
function subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly = false, $userids = [], $fetchpercentage = false) {

    if (empty($refcourseid)) {
        throw new coding_exception('Empty referenced course id');
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $return = new stdClass();
    $return->grades = [];

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
            $userids = [$userids];
        }

        $cm = get_coursemodule_from_instance("subcourse", $subcourseid);
        $context = context_module::instance($cm->id);

        $users = get_users_by_capability($context, 'mod/subcourse:begraded', 'u.id,u.lastname',
                                         'u.lastname', '', '', '', '', false, true);

        foreach ($users as $user) {
            if ($userids && !in_array($user->id, $userids)) {
                continue;
            }

            $grade = new grade_grade(['itemid' => $refgradeitem->id, 'userid' => $user->id]);

            $return->grades[$user->id] = new stdClass();
            $return->grades[$user->id]->userid = $user->id;
            $return->grades[$user->id]->feedback = $grade->feedback;
            $return->grades[$user->id]->feedbackformat = $grade->feedbackformat;
            $return->grades[$user->id]->hidden = $grade->hidden;

            if ($grade->finalgrade === null) {
                // No grade set yet.
                $return->grades[$user->id]->rawgrade = null;

            } else if (empty($fetchpercentage)) {
                // Fetch the raw value of the final grade in the referenced course.
                $return->grades[$user->id]->rawgrade = $grade->finalgrade;

            } else {
                // Re-calculate the value so that the displayed percentage matches.
                // This may make difference when there are excluded grades in the referenced course.
                if ($grade->rawgrademax > 0) {
                    $ratio = ($grade->finalgrade - $grade->rawgrademin) / ($grade->rawgrademax - $grade->rawgrademin);
                    $fakevalue = $return->grademin + $ratio * ($return->grademax - $return->grademin);
                    $return->grades[$user->id]->rawgrade = grade_floatval($fakevalue);

                } else {
                    $return->grades[$user->id]->rawgrade = 0;
                }
            }
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
 * @param bool $fetchpercentage Re-calculate the grade value so that the displayed percentage matches the original.
 * @return int GRADE_UPDATE_OK etc
 */
function subcourse_grades_update($courseid, $subcourseid, $refcourseid, $itemname = null,
        $gradeitemonly = false, $reset = false, $userids = [], $fetchpercentage = null) {
    global $DB;

    if (empty($refcourseid)) {
        return GRADE_UPDATE_FAILED;
    }

    if (!$DB->record_exists('course', ['id' => $refcourseid])) {
        return GRADE_UPDATE_FAILED;
    }

    if (!$gradeitemonly && $fetchpercentage === null) {
        debugging('Performance: The caller should provide the fetchpercentage value to avoid an extra DB call.', DEBUG_DEVELOPER);
        $fetchpercentage = $DB->get_field('subcourse', 'fetchpercentage', ['id' => $subcourseid]);
    }

    $fetchedfields = subcourse_get_fetched_item_fields();

    $refgrades = subcourse_fetch_refgrades($subcourseid, $refcourseid, $gradeitemonly, $userids, $fetchpercentage);

    if (!empty($refgrades->localremotescale)) {
        // Unable to fetch remote grades - local scale is used in the remote course.
        return GRADE_UPDATE_FAILED;
    }

    $params = [];

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

    $result = grade_update('mod/subcourse', $courseid, 'mod', 'subcourse', $subcourseid, 0, $grades, $params);

    // The {@see grade_update()} does not change the grade hidden state so we need to perform it manually now.
    if (!$gradeitemonly && $result == GRADE_UPDATE_OK) {
        $gi = grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $courseid,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $subcourseid,
            'itemnumber' => 0
        ]);

        $gs = grade_grade::fetch_all(['itemid' => $gi->id]);

        if (!empty($gs)) {
            foreach ($gs as $g) {
                if (isset($refgrades->grades[$g->userid])) {
                    if ($refgrades->grades[$g->userid]->hidden != $g->hidden) {
                        $g->grade_item = $gi;
                        $g->set_hidden($refgrades->grades[$g->userid]->hidden);
                    }
                }
            }
        }
    }

    return $result;
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

    if (!$DB->get_record('scale', ['id' => $scaleid, 'courseid' => 0], 'id')) {
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
        $subcourseids = [$subcourseids];
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
    return ['gradetype', 'grademax', 'grademin', 'scaleid', 'hidden'];
}

/**
 * Return if the user has a grade for the activity and the string representation of the grade.
 *
 * @param stdClass $subcourse Subcourse activity record with id and course properties set
 * @param int $userid User id to get the grade for
 * @return string $strgrade
 */
function subcourse_get_current_grade(stdClass $subcourse, int $userid): ?string {

    $currentgrade = grade_get_grades($subcourse->course, 'mod', 'subcourse', $subcourse->id, $userid);
    $strgrade = null;

    if (!empty($currentgrade->items[0]->grades)) {
        $currentgrade = reset($currentgrade->items[0]->grades);

        if (isset($currentgrade->grade) && !($currentgrade->hidden)) {
            $strgrade = $currentgrade->str_grade;
        }
    }

    return $strgrade;
}

/**
 * Mark the course module as viewed by the user.
 *
 * @param stdClass $subcourse Subcourse record.
 * @param context $context Course module context.
 * @param stdClass $course Course record.
 * @param cm_info|object $cm Course module info.
 */
function subcourse_set_module_viewed(stdClass $subcourse, context $context, stdClass $course, $cm) {
    global $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    $event = \mod_subcourse\event\course_module_viewed::create([
        'objectid' => $subcourse->id,
        'context' => $context,
    ]);

    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('subcourse', $subcourse);

    $event->trigger();
}
