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

namespace mod_subcourse;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/upgradelib.php');
require_once($CFG->dirroot . '/mod/subcourse/db/upgrade.php');
require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

/**
 * Upgrade tests for mod_subcourse.
 *
 * @package     mod_subcourse
 * @category    test
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upgrade_test extends \advanced_testcase {
    /**
     * Test legacy core pass-grade completion is migrated to the Subcourse strict pass-grade rule.
     *
     * @covers ::xmldb_subcourse_upgrade
     */
    public function test_upgrade_migrates_core_passgrade_completion(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $refcourse = $generator->create_course();
        $passinguser = $generator->create_user();
        $failinguser = $generator->create_user();
        $overrideuser = $generator->create_user();

        foreach ([$passinguser, $failinguser, $overrideuser] as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
            $generator->enrol_user($user->id, $refcourse->id, 'student');
        }

        $subcourse = $generator->create_module('subcourse', [
            'course' => $course->id,
            'refcourse' => $refcourse->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncourse' => 0,
            'completionpassgradesubcourse' => 0,
        ]);
        $cm = get_coursemodule_from_instance('subcourse', $subcourse->id, $course->id, false, MUST_EXIST);

        $DB->set_field('course_modules', 'completionpassgrade', 1, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $cm->id]);

        subcourse_grades_update($course->id, $subcourse->id, $refcourse->id, $subcourse->name, true);
        $gradeitem = \grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $course->id,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $subcourse->id,
            'itemnumber' => 0,
        ]);
        $gradeitem->gradepass = 75;
        $gradeitem->update('mod/subcourse');

        $this->set_grade($gradeitem, $passinguser->id, 80);
        $this->set_grade($gradeitem, $failinguser->id, 50);
        $this->set_grade($gradeitem, $overrideuser->id, 50);

        $this->set_completion_record($cm->id, $passinguser->id, COMPLETION_INCOMPLETE);
        $this->set_completion_record($cm->id, $failinguser->id, COMPLETION_COMPLETE);
        $this->set_completion_record($cm->id, $overrideuser->id, COMPLETION_COMPLETE, get_admin()->id);

        set_config('version', 2026090300, 'mod_subcourse');
        $this->assertTrue(\xmldb_subcourse_upgrade(2026090300));

        $this->assertEquals(1, $DB->get_field('subcourse', 'completionpassgradesubcourse', ['id' => $subcourse->id]));
        $this->assertEquals(0, $DB->get_field('course_modules', 'completionpassgrade', ['id' => $cm->id]));
        $this->assertNull($DB->get_field('course_modules', 'completiongradeitemnumber', ['id' => $cm->id]));

        $this->assert_completion_state($cm->id, $passinguser->id, COMPLETION_COMPLETE);
        $this->assert_completion_state($cm->id, $failinguser->id, COMPLETION_INCOMPLETE);
        $this->assert_completion_state($cm->id, $overrideuser->id, COMPLETION_COMPLETE);
    }

    /**
     * Set a grade for the supplied grade item.
     *
     * @param \grade_item $gradeitem Grade item.
     * @param int $userid User id.
     * @param float $grade Grade value.
     */
    private function set_grade(\grade_item $gradeitem, int $userid, float $grade): void {
        $gradegrade = new \grade_grade([
            'itemid' => $gradeitem->id,
            'userid' => $userid,
            'rawgrade' => $grade,
            'rawgrademin' => 0,
            'rawgrademax' => 100,
            'finalgrade' => $grade,
        ], false);
        $gradegrade->insert('mod/subcourse');
    }

    /**
     * Set an activity completion record.
     *
     * @param int $cmid Course module id.
     * @param int $userid User id.
     * @param int $state Completion state.
     * @param int|null $overrideby User id of manual override owner.
     */
    private function set_completion_record(int $cmid, int $userid, int $state, ?int $overrideby = null): void {
        global $DB;

        $record = (object) [
            'coursemoduleid' => $cmid,
            'userid' => $userid,
            'completionstate' => $state,
            'viewed' => 0,
            'overrideby' => $overrideby,
            'timemodified' => time(),
        ];

        if ($existing = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cmid, 'userid' => $userid])) {
            $record->id = $existing->id;
            $DB->update_record('course_modules_completion', $record);
        } else {
            $DB->insert_record('course_modules_completion', $record);
        }
    }

    /**
     * Assert a user's activity completion state.
     *
     * @param int $cmid Course module id.
     * @param int $userid User id.
     * @param int $expected Expected completion state.
     */
    private function assert_completion_state(int $cmid, int $userid, int $expected): void {
        global $DB;

        $actual = $DB->get_field(
            'course_modules_completion',
            'completionstate',
            ['coursemoduleid' => $cmid, 'userid' => $userid],
            MUST_EXIST
        );
        $this->assertSame($expected, (int)$actual);
    }
}
