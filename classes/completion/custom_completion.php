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
        require_once($CFG->dirroot.'/completion/completion_completion.php');

        $this->validate_rule($rule);

        $subcourse = $DB->get_record('subcourse', ['id' => $this->cm->instance], 'id,refcourse,completioncourse', MUST_EXIST);

        if (empty($subcourse->completioncourse)) {
            // The rule not enabled, return early.
            return $type;
        }

        if (empty($subcourse->refcourse)) {
            // Misconfigured subcourse instance, behave as if was not enabled.
            return $type;
        }

        // Check if the referenced course is completed.
        $coursecompletion = new \completion_completion(['userid' => $this->userid, 'course' => $subcourse->refcourse]);

        return $coursecompletion->is_complete() ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completioncourse'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completioncourse' => get_string('completioncourse', 'subcourse')];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completioncourse',
        ];
    }
}
