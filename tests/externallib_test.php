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
 * Provides {@see \mod_subcourse\externallib_test} class.
 *
 * @package     mod_subcourse
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse;

use core_external\external_api;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Unit tests for the external functions provided by the plugin.
 *
 * @package   mod_subcourse
 * @category  test
 * @copyright 2020 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @runTestsInSeparateProcesses
 */
final class externallib_test extends externallib_advanced_testcase {
    /**
     * Test the external function mod_subcourse_view_subcourse.
     *
     * @covers \mod_subcourse\external\view_subcourse::execute
     */
    public function test_view_subcourse(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $metacourse = $generator->create_course();
        $student = $generator->create_user();
        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
        ]);
        $generator->enrol_user($student->id, $metacourse->id, 'student');
        $this->setUser($student);

        $returnvalue = \mod_subcourse\external\view_subcourse::execute($subcourse->id);

        // Clean value to simulate the web service server.
        $returnvalue = external_api::clean_returnvalue(\mod_subcourse\external\view_subcourse::execute_returns(), $returnvalue);

        $this->assertEquals(true, $returnvalue['status']);
    }

    /**
     * Test that the external function enforces the view capability.
     *
     * @covers \mod_subcourse\external\view_subcourse::execute
     */
    public function test_view_subcourse_requires_view_capability(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $metacourse = $generator->create_course();
        $student = $generator->create_user();
        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
        ]);
        $generator->enrol_user($student->id, $metacourse->id, 'student');

        $cm = get_coursemodule_from_instance('subcourse', $subcourse->id, $metacourse->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('mod/subcourse:view', CAP_PREVENT, $studentroleid, $context->id);

        $this->setUser($student);
        // Context validation rejects modules whose view capability is denied.
        $this->expectException(\core\exception\require_login_exception::class);

        \mod_subcourse\external\view_subcourse::execute($subcourse->id);
    }
}
