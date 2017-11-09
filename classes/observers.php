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
 * Provides the {@link mod_subcourse\observers} class.
 *
 * @package    mod_subcourse
 * @copyright  2014 Vadim Dvorovenko (Vadimon@mail.ru)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse;

use completion_info;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

/**
 * Implements the module's event observers.
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * User graded
     *
     * @param \core\event\user_graded $event
     * @return void
     */
    public static function user_graded(\core\event\user_graded $event) {
        global $DB;

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        $subcourses = $DB->get_records('subcourse', array('refcourse' => $courseid));

        foreach ($subcourses as $subcourse) {
            $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse,
                null, false, false, $userid);
        }
    }

    /**
     * Role assigned
     *
     * Every time new user is enrolled into course we should fetch all subcourses again,
     * because user can be previously enrolled into subcourse
     *
     * @param \core\event\role_assigned $event
     * @return void
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $DB;

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        $subcourses = $DB->get_records('subcourse', array('course' => $courseid));

        foreach ($subcourses as $subcourse) {
            $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse,
                null, false, false, $userid);
        }
    }

    /**
     * Handle the course_completed event.
     *
     * Notify all subcourse instances with the relevant completion rule enabled
     * that the user completed the referenced course - so that  they can be eventually
     * marked as completed, too.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/completionlib.php');

        $courseid = $event->courseid;
        $userid = $event->relateduserid;

        // Get all subcourses that have the completed course as the referenced one.
        $subcourses = $DB->get_records('subcourse', ['refcourse' => $courseid, 'completioncourse' => 1]);

        if (empty($subcourses)) {
            // No subcourse interested in this.
            return;
        }

        // Load the courses where the subcourses are located in.
        $courseids = [];

        foreach ($subcourses as $subcourse) {
            $courseids[$subcourse->course] = true;
        }

        $courses = $DB->get_records_list('course', 'id', array_keys($courseids), '', '*');

        foreach ($subcourses as $subcourse) {
            $course = $courses[$subcourse->course];
            $cm = get_coursemodule_from_instance('subcourse', $subcourse->id, $course->id);
            $completion = new completion_info($course);

            if ($completion->is_enabled($cm)) {
                // Notify the subcourse to check the completion status.
                $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
            }
        }
    }
}