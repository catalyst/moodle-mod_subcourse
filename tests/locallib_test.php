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
 * Provides {@see mod_subcourse_locallib_testcase} class.
 *
 * @package     mod_subcourse
 * @category    test
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

/**
 * Unit tests for the functions in the locallib.php file.
 *
 * @copyright 2020 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Test that it is possible to fetch grades from the referenced course.
     *
     * @covers ::subcourse_grades_update
     */
    public function test_subcourse_grades_update(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $metacourse = $generator->create_course();
        $refcourse = $generator->create_course();

        $student1 = $generator->create_user();
        $student2 = $generator->create_user();

        $generator->enrol_user($student1->id, $metacourse->id, 'student');
        $generator->enrol_user($student1->id, $refcourse->id, 'student');
        $generator->enrol_user($student2->id, $metacourse->id, 'student');
        $generator->enrol_user($student2->id, $refcourse->id, 'student');

        // Give some grades in the referenced course.
        $gi = new \grade_item($generator->create_grade_item(['courseid' => $refcourse->id]), false);
        $gi->update_final_grade($student1->id, 90, 'test');
        $gi->update_final_grade($student2->id, 60, 'test');
        $gi->force_regrading();
        grade_regrade_final_grades($refcourse->id);

        // Create the Subcourse module instance in the metacourse, representing the final grade in the referenced course.
        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
            'refcourse' => $refcourse->id,
        ]);

        $strgrade = subcourse_get_current_grade($subcourse, $student1->id);
        $this->assertNull($strgrade);

        // Fetch all students' grades from the refcourse to the metacourse.
        subcourse_grades_update($metacourse->id, $subcourse->id, $refcourse->id, null, false, false, [], false);

        // Check the grades were correctly fetched.
        $metagrades = grade_get_grades($metacourse->id, 'mod', 'subcourse', $subcourse->id, [$student1->id, $student2->id]);
        $this->assertEquals(90, $metagrades->items[0]->grades[$student1->id]->grade);
        $this->assertEquals(60, $metagrades->items[0]->grades[$student2->id]->grade);

        $strgrade = subcourse_get_current_grade($subcourse, $student1->id);
        $this->assertEquals('90.00', $strgrade);

        // Update the grades in the referenced course.
        $gi->update_final_grade($student1->id, 80, 'test');
        $gi->update_final_grade($student2->id, 50, 'test');
        $gi->force_regrading();
        grade_regrade_final_grades($refcourse->id);

        // Fetch again, this time only one student's grades.
        subcourse_grades_update($metacourse->id, $subcourse->id, $refcourse->id, null, false, false, [$student1->id], false);

        // Re-check that the student1's grade was updated succesfully.
        $metagrades = grade_get_grades($metacourse->id, 'mod', 'subcourse', $subcourse->id, [$student1->id, $student2->id]);
        $this->assertEquals(80, $metagrades->items[0]->grades[$student1->id]->grade);
    }

    /**
     * Test that only requested users with the begraded capability have their grades fetched.
     *
     * @covers ::subcourse_fetch_refgrades
     * @covers ::subcourse_get_gradeable_userids
     */
    public function test_subcourse_fetch_refgrades_requested_users(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $metacourse = $generator->create_course();
        $refcourse = $generator->create_course();

        $student1 = $generator->create_user();
        $student2 = $generator->create_user();
        $teacher = $generator->create_user();
        $nograde = $generator->create_user();

        $generator->enrol_user($student1->id, $metacourse->id, 'student');
        $generator->enrol_user($student1->id, $refcourse->id, 'student');
        $generator->enrol_user($student2->id, $metacourse->id, 'student');
        $generator->enrol_user($student2->id, $refcourse->id, 'student');
        $generator->enrol_user($teacher->id, $metacourse->id, 'editingteacher');
        $generator->enrol_user($teacher->id, $refcourse->id, 'student');
        $generator->enrol_user($nograde->id, $metacourse->id, 'student');

        $gi = new \grade_item($generator->create_grade_item(['courseid' => $refcourse->id]), false);
        $gi->update_final_grade($student1->id, 90, 'test');
        $gi->update_final_grade($student2->id, 60, 'test');
        $gi->update_final_grade($teacher->id, 70, 'test');
        $gi->force_regrading();
        grade_regrade_final_grades($refcourse->id);

        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
            'refcourse' => $refcourse->id,
        ]);

        // A single user ID limits the result to that user only.
        $refgrades = subcourse_fetch_refgrades($subcourse->id, $refcourse->id, false, $student1->id);
        $this->assertEquals([$student1->id], array_keys($refgrades->grades));
        $this->assertEquals(90, $refgrades->grades[$student1->id]->rawgrade);

        // Users without the capability, the admin, the guest and invalid IDs are skipped.
        $refgrades = subcourse_fetch_refgrades($subcourse->id, $refcourse->id, false, [
            $student2->id,
            $teacher->id,
            $nograde->id,
            get_admin()->id,
            $CFG->siteguest,
            0,
            $student2->id,
        ]);
        $this->assertEqualsCanonicalizing([$student2->id, $nograde->id], array_keys($refgrades->grades));
        $this->assertEquals(60, $refgrades->grades[$student2->id]->rawgrade);
        $this->assertNull($refgrades->grades[$nograde->id]->rawgrade);

        // Without user IDs all gradeable users are returned.
        $refgrades = subcourse_fetch_refgrades($subcourse->id, $refcourse->id);
        $this->assertEqualsCanonicalizing(
            [$student1->id, $student2->id, $nograde->id],
            array_keys($refgrades->grades),
        );
    }

    /**
     * Test that calling {see subcourse_set_module_viewed()} does not raise errors.
     *
     * @covers ::subcourse_set_module_viewed
     */
    public function test_subcourse_set_module_viewed(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $metacourse = $generator->create_course();
        $student = $generator->create_user();
        $subcourse = $generator->create_module('subcourse', [
            'course' => $metacourse->id,
        ]);
        $generator->enrol_user($student->id, $metacourse->id, 'student');

        [$course, $cm] = get_course_and_cm_from_instance($subcourse->id, 'subcourse');
        $context = \context_module::instance($cm->id);

        subcourse_set_module_viewed($subcourse, $context, $course, $cm);
    }
}
