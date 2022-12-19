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

/**
 * Obtains the automatic completion state for this subcourse.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function subcourse_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/completion/completion_completion.php');

    $subcourse = $DB->get_record('subcourse', ['id' => $cm->instance], 'id,refcourse,completioncourse', MUST_EXIST);

    if (empty($subcourse->completioncourse)) {
        // The rule not enabled, return early.
        return $type;
    }

    if (empty($subcourse->refcourse)) {
        // Misconfigured subcourse instance, behave as if was not esnabled.
        return $type;
    }

    // Check if the referenced course is completed.
    $coursecompletion = new completion_completion(['userid' => $userid, 'course' => $subcourse->refcourse]);

    return $coursecompletion->is_complete();
}
