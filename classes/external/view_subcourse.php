<?php
// This file is part of Moodle - https://moodle.org/
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
 * Provides {@see \mod_subcourse\external\view_subcourse} class.
 *
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;

/**
 * Implements the mod_subcourse_view_subcourse external function.
 *
 * @package   mod_subcourse
 * @category  external
 * @copyright 2020 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_subcourse extends external_api {

    /**
     * Describes the parameters for view_subcourse.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {

        return new external_function_parameters([
            'subcourseid' => new external_value(PARAM_INT, 'Subcourse instance id'),
        ]);
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $subcourseid subcourse instance id
     * @return array of warnings and status result
     * @throws moodle_exception
     */
    public static function execute($subcourseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

        $params = ['subcourseid' => $subcourseid];
        $params = self::validate_parameters(self::execute_parameters(), $params);
        $warnings = [];

        $subcourse = $DB->get_record('subcourse', ['id' => $params['subcourseid']], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($subcourse, 'subcourse');
        $context = \context_module::instance($cm->id);

        self::validate_context($context);

        subcourse_set_module_viewed($subcourse, $context, $course, $cm);

        $result = [
            'status' => true,
            'warnings' => $warnings,
        ];

        return $result;
    }

    /**
     * Describes the view_subcourse return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'Status: true if success'),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
