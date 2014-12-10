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
 * @package    mod_subcourse
 * @copyright  2014 Vadim Dvorovenko (Vadimon@mail.ru)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/subcourse/lib.php');

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

        $subcourses = $DB->get_records('subcourse', array('refcourse' => $courseid));
        foreach ($subcourses as $subcourse) {
            $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
            if ($result == GRADE_UPDATE_OK) {
                $updatedids[] = $subcourse->id;
            }
        }
        if (!empty($updatedids)) {
            subcourse_update_timefetched($updatedids);
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

        $subcourses = $DB->get_records('subcourse', array('course' => $courseid));
        foreach ($subcourses as $subcourse) {
            $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
            if ($result == GRADE_UPDATE_OK) {
                $updatedids[] = $subcourse->id;
            }
        }
        if (!empty($updatedids)) {
            subcourse_update_timefetched($updatedids);
        }
    }

}