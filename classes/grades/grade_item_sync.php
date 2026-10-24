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
 * Synchronises Subcourse grade item fields after grade updates.
 *
 * @package     mod_subcourse
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\grades;

/**
 * Synchronises grade item fields that grade_update() does not manage directly.
 */
class grade_item_sync {
    /**
     * Synchronise grade item metadata, grade hidden states and completion after a grade update.
     *
     * @param int $courseid Parent course ID.
     * @param int $subcourseid Subcourse instance ID.
     * @param \stdClass $refgrades Grade data fetched from the referenced course.
     * @param bool $gradeitemonly Whether only the grade item should be updated.
     * @param array|null $grades Grade records submitted to grade_update(), or null on reset.
     * @return void
     */
    public static function after_grade_update(
        int $courseid,
        int $subcourseid,
        \stdClass $refgrades,
        bool $gradeitemonly,
        ?array $grades
    ): void {
        $gradeitem = self::get_grade_item($courseid, $subcourseid);
        if (!$gradeitem) {
            return;
        }

        self::sync_gradepass($gradeitem, $refgrades);

        if ($gradeitemonly) {
            return;
        }

        self::sync_hidden_states($gradeitem, $refgrades);
        self::sync_completion($subcourseid, $grades);
    }

    /**
     * Return the Subcourse grade item.
     *
     * @param int $courseid Parent course ID.
     * @param int $subcourseid Subcourse instance ID.
     * @return \grade_item|null
     */
    private static function get_grade_item(int $courseid, int $subcourseid): ?\grade_item {
        $gradeitem = \grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $courseid,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $subcourseid,
            'itemnumber' => 0,
        ]);

        return $gradeitem ?: null;
    }

    /**
     * Synchronise the grade-to-pass value from the referenced grade item.
     *
     * @param \grade_item $gradeitem Subcourse grade item.
     * @param \stdClass $refgrades Grade data fetched from the referenced course.
     * @return void
     */
    private static function sync_gradepass(\grade_item $gradeitem, \stdClass $refgrades): void {
        if (!property_exists($refgrades, 'gradepass')) {
            return;
        }

        $gradepass = $refgrades->gradepass ?? 0;
        if (\grade_floats_different($gradeitem->gradepass, $gradepass)) {
            $gradeitem->gradepass = $gradepass;
            $gradeitem->update('mod/subcourse');
        }
    }

    /**
     * Synchronise hidden states of fetched grades.
     *
     * @param \grade_item $gradeitem Subcourse grade item.
     * @param \stdClass $refgrades Grade data fetched from the referenced course.
     * @return void
     */
    private static function sync_hidden_states(\grade_item $gradeitem, \stdClass $refgrades): void {
        if (empty($refgrades->grades)) {
            return;
        }

        $gradegrades = \grade_grade::fetch_all(['itemid' => $gradeitem->id]);
        if (empty($gradegrades)) {
            return;
        }

        foreach ($gradegrades as $gradegrade) {
            if (!isset($refgrades->grades[$gradegrade->userid])) {
                continue;
            }

            if ($refgrades->grades[$gradegrade->userid]->hidden != $gradegrade->hidden) {
                $gradegrade->grade_item = $gradeitem;
                $gradegrade->set_hidden($refgrades->grades[$gradegrade->userid]->hidden);
            }
        }
    }

    /**
     * Synchronise completion for users whose Subcourse grade was fetched.
     *
     * @param int $subcourseid Subcourse instance ID.
     * @param array|null $grades Grade records submitted to grade_update(), or null on reset.
     * @return void
     */
    private static function sync_completion(int $subcourseid, ?array $grades): void {
        global $DB;

        if (empty($grades)) {
            return;
        }

        $subcourse = $DB->get_record(
            'subcourse',
            ['id' => $subcourseid],
            'id, course, refcourse, completioncourse, completioncoursereversible, completionpassgradesubcourse',
            IGNORE_MISSING
        );

        if ($subcourse) {
            \mod_subcourse\completion\refcourse_completion_sync::sync_users($subcourse, array_keys($grades));
        }
    }
}
