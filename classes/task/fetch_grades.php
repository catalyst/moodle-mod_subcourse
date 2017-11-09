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
 * Provides the {@link mod_subcourse\task\fetch_grades} class.
 *
 * @package     mod_subcourse
 * @category    task
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/subcourse/locallib.php');

/**
 * Fetches remote grades into all subcourse instances
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fetch_grades extends \core\task\scheduled_task {

    /**
     * Returns a descriptive name for this task shown to admins
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskfetchgrades', 'mod_subcourse');
    }

    /**
     * Performs the task
     *
     * @throws moodle_exception on an error (the job will be retried)
     */
    public function execute() {
        global $DB;

        $subcourses = $DB->get_records("subcourse", null, "", "id, course, refcourse");

        if (empty($subcourses)) {
            return;
        }

        $updatedids = array();

        foreach ($subcourses as $subcourse) {

            if (empty($subcourse->refcourse)) {
                mtrace("Subcourse {$subcourse->id}: no referenced course configured ... skipped");
                continue;
            }

            mtrace("Subcourse {$subcourse->id}: fetching grades from course {$subcourse->refcourse} ".
               "to course {$subcourse->course} ... ", "");
            $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);

            if ($result == GRADE_UPDATE_OK) {
                $updatedids[] = $subcourse->id;
                mtrace("ok");

            } else {
                mtrace("failed with error code ".$result);
            }
        }

        if (!empty($updatedids)) {
            subcourse_update_timefetched($updatedids);
        }
    }
}
