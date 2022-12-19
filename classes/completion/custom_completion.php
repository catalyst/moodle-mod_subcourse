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

namespace mod_subcourse\completion;

/**
 * Custom completion rules for mod_subcourse
 *
 * @package     mod_subcourse
 * @copyright   2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
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
        global $DB;

        $this->validate_rule($rule);

        return subcourse_get_completion_state($this->cm->get_course(), $this->cm, $this->userid, 0);
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
        return ['completioncourse'];
    }
}
