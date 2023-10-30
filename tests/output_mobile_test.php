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
 * Provides {@see mod_subcourse_output_mobile_testcase} class.
 *
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Unit tests for the methods provided by the {@see \mod_subcourse\output\mobile} class.
 *
 * @package   mod_subcourse
 * @category  test
 * @copyright 2020 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output_mobile_test extends \advanced_testcase {

    /**
     * Test the return value of the main_view() method.
     *
     * @covers ::main_view
     */
    public function test_main_view() {

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $metacourse = $generator->create_course();
        $refcourse = $generator->create_course();
        $student = $generator->create_user();

        $generator->enrol_user($student->id, $metacourse->id, 'student');
        $generator->enrol_user($student->id, $refcourse->id, 'student');

        // Give some grades in the referenced course.
        $gi = new \grade_item($generator->create_grade_item(['courseid' => $refcourse->id]), false);
        $gi->update_final_grade($student->id, 90, 'test');
        $gi->force_regrading();
        grade_regrade_final_grades($refcourse->id);

        // Create the Subcourse module instance in the metacourse, representing the final grade in the referenced course.
        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
            'refcourse' => $refcourse->id,
        ]);

        // Fetch all students' grades from the refcourse to the metacourse.
        subcourse_grades_update($metacourse->id, $subcourse->id, $refcourse->id, null, false, false, [], false);

        // Get the data for the student using the Mobile App.
        $this->setUser($student);

        // Ionic5 compatible view for the app version 3.9.5.
        $mainview3950 = \mod_subcourse\output\mobile::main_view([
            'cmid' => $subcourse->cmid,
            'courseid' => $metacourse->id,
            'appversioncode' => 3950,
        ]);

        $this->assertEquals('main', $mainview3950['templates'][0]['id']);
        $this->assertStringContainsString('plugin.mod_subcourse.currentgrade', $mainview3950['templates'][0]['html']);

        // Ionic3 compatible view for the app version 3.9.4.
        $mainview3940 = \mod_subcourse\output\mobile::main_view([
            'cmid' => $subcourse->cmid,
            'courseid' => $metacourse->id,
            'appversioncode' => 3940,
        ]);

        $this->assertEquals('main', $mainview3940['templates'][0]['id']);
        $this->assertStringContainsString('plugin.mod_subcourse.currentgrade', $mainview3940['templates'][0]['html']);
    }
}
