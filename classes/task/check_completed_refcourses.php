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
 * Provides {@link \mod_subcourse\task\check_completed_refcourses} class
 *
 * @package     mod_subcourse
 * @category    task
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\task;

use completion_completion;
use completion_info;
use context_course;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/subcourse/locallib.php');

/**
 * Makes sure that all subcourse instances are marked as completed when they should be.
 *
 * Normally, completed course triggers the subcourse completion automatically
 * via observing the event. This task is there for rechecking the completions to catch
 * up with courses that were completed in the past (and the event was missed).
 */
class check_completed_refcourses extends \core\task\scheduled_task {

    /**
     * Returns a descriptive name for this task shown to admins
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcheckcompletedrefcourses', 'mod_subcourse');
    }

    /**
     * Performs the task
     *
     * @throws moodle_exception on an error (the job will be retried)
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/completionlib.php');
        require_once($CFG->dirroot.'/completion/completion_completion.php');

        if (!completion_info::is_enabled_for_site()) {
            mtrace("Completion tracking not enabled on this site");
            return;
        }

        // Get all subcourses that have completion rule based on refcourse completed.
        $rs = $DB->get_recordset('subcourse', ['completioncourse' => 1], 'course');

        // Note the subcourses are sorted by their course. We cache the course
        // record and the list of enrolled participants for all subcourse
        // instances within one course in the following variable.
        $cache = [];

        foreach ($rs as $subcourse) {
            $cm = get_coursemodule_from_instance('subcourse', $subcourse->id);

            if (empty($subcourse->refcourse)) {
                mtrace("Subcourse {$subcourse->id}: no referenced course configured ... skipped");
                continue;
            }

            if (!isset($cache[$subcourse->course])) {
                // Load the course with this subcourse and students enrolled to it.
                // We do not need data from the previous course any more.
                $course = $DB->get_record('course', ['id' => $subcourse->course]);
                $coursecontext = context_course::instance($course->id);
                $cache = [
                    $course->id => (object)[
                        'course' => $course,
                        'participants' => get_enrolled_users($coursecontext, 'mod/subcourse:begraded', 0, "u.id"),
                    ]
                ];
            }

            $completion = new completion_info($cache[$subcourse->course]->course);

            if (!$completion->is_enabled($cm)) {
                mtrace("Subcourse {$subcourse->id}: completion tracking not enabled ... skipped");
                continue;
            }

            mtrace("Subcourse {$subcourse->id}: checking refcourse {$subcourse->refcourse} completions ... ");

            foreach (array_keys($cache[$subcourse->course]->participants) as $userid) {
                $coursecompletion = new completion_completion(['userid' => $userid, 'course' => $subcourse->refcourse]);
                if ($coursecompletion->is_complete()) {
                    // Notify the subcourse to check the completion status.
                    mtrace(" - user {$userid}: completed - notifying subcourse");
                    $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
                }
            }

            mtrace(" ... checked ".count($cache[$subcourse->course]->participants)." users");
        }

        $rs->close();
    }
}
