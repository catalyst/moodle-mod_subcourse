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

declare(strict_types=1);

namespace mod_subcourse\completion;

/**
 * Custom completion rules for mod_subcourse
 *
 * @package     mod_subcourse
 * @copyright   Catalyst IT
 * @author      Dan Marsden
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * Returns completion state of the custom completion rules
     *
     * @param string $rule
     * @return integer
     */
    public function get_state(string $rule): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/completion/completion_completion.php');
        require_once($CFG->libdir . '/gradelib.php');

        $this->validate_rule($rule);

        $subcourse = $DB->get_record(
            'subcourse',
            ['id' => $this->cm->instance],
            'id,refcourse,completioncourse,completionpassgradesubcourse',
            MUST_EXIST
        );

        if ($rule === 'completioncourse') {
            return $this->get_refcourse_completion_state($subcourse);
        }

        return $this->get_passgrade_completion_state();
    }

    /**
     * Return completion state for referenced course completion.
     *
     * @param \stdClass $subcourse Subcourse record.
     * @return int Completion state.
     */
    protected function get_refcourse_completion_state(\stdClass $subcourse): int {
        if (empty($subcourse->refcourse)) {
            // Misconfigured subcourse instance, behave as if was not enabled.
            return COMPLETION_INCOMPLETE;
        }

        // Check if the referenced course is completed.
        $coursecompletion = new \completion_completion(['userid' => $this->userid, 'course' => $subcourse->refcourse]);

        return $coursecompletion->is_complete() ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Return strict completion state for Subcourse passing-grade completion.
     *
     * @return int Completion state.
     */
    protected function get_passgrade_completion_state(): int {
        $gradeitem = \grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $this->cm->course,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $this->cm->instance,
            'itemnumber' => 0,
        ]);

        $gradepass = $gradeitem ? (float)$gradeitem->gradepass : 0.0;
        if (!$gradeitem || !grade_floats_different($gradepass, 0.0)) {
            return COMPLETION_INCOMPLETE;
        }

        $grade = \grade_grade::fetch([
            'itemid' => $gradeitem->id,
            'userid' => $this->userid,
        ]);

        if (!$grade || $grade->finalgrade === null) {
            return COMPLETION_INCOMPLETE;
        }

        return $grade->finalgrade >= $gradepass ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completioncourse', 'completionpassgradesubcourse'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completioncourse' => get_string('completioncourse', 'subcourse'),
            'completionpassgradesubcourse' => get_string('completionpassgradesubcourse_text', 'subcourse'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpassgradesubcourse',
            'completioncourse',
        ];
    }
}
