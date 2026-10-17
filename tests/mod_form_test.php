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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_subcourse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/subcourse/mod_form.php');

/**
 * Tests for the referenced course validation of the subcourse form.
 *
 * @package    mod_subcourse
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_subcourse_mod_form::class)]
final class mod_form_test extends \advanced_testcase {
    /**
     * Run the form validation as the given user.
     *
     * @param \stdClass $course course the activity is added to
     * @param int $refcourse referenced course id
     * @return array validation errors
     */
    private function validate(\stdClass $course, int $refcourse): array {
        $current = (object) ['course' => $course->id, 'section' => 0, 'modulename' => 'subcourse',
            'instance' => 0, 'coursemodule' => 0, 'add' => 'subcourse', 'update' => 0, 'return' => 0, 'sr' => 0];
        set_config('enableavailability', 0);
        global $PAGE;
        $PAGE->set_course($course);
        $form = new \mod_subcourse_mod_form($current, 0, null, $course);
        return $form->validation(['refcourse' => $refcourse, 'name' => 'x', 'modulename' => 'subcourse',
            'instance' => 0, 'coursemodule' => 0, 'cmidnumber' => ''], []);
    }

    #[Group('baseline')]
    /**
     * A teacher must not reference a course they cannot read grades from.
     *
     * @return void
     */
    public function test_validation_rejects_foreign_course(): void {
        $this->resetAfterTest();
        $own = $this->getDataGenerator()->create_course();
        $foreign = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($own, 'editingteacher');
        $this->setUser($teacher);

        $errors = $this->validate($own, $foreign->id);

        $this->assertArrayHasKey('refcourse', $errors);
    }

    #[Group('baseline')]
    /**
     * A course with grade access stays selectable.
     *
     * @return void
     */
    public function test_validation_accepts_course_with_grade_access(): void {
        $this->resetAfterTest();
        $own = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($own, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $other->id, 'editingteacher');
        $this->setUser($teacher);

        $errors = $this->validate($own, $other->id);

        $this->assertArrayNotHasKey('refcourse', $errors);
    }
}
