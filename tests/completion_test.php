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

use completion_completion;
use completion_info;
use mod_subcourse\completion\custom_completion;
use mod_subcourse\completion\refcourse_completion_sync;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');
require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

/**
 * Unit tests for subcourse completion behaviour.
 *
 * @package     mod_subcourse
 * @category    test
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class completion_test extends \advanced_testcase {
    /**
     * Create a pair of courses, enrolled users and a subcourse instance.
     *
     * @param bool $reversible Whether referenced course completion can reverse parent completion.
     * @return array Test fixture.
     */
    private function create_completion_fixture(bool $reversible = false): array {
        global $DB;

        $generator = $this->getDataGenerator();

        $maincourse = $generator->create_course(['enablecompletion' => 1]);
        $refcourse = $generator->create_course(['enablecompletion' => 1]);
        $student1 = $generator->create_user();
        $student2 = $generator->create_user();

        $generator->enrol_user($student1->id, $maincourse->id, 'student');
        $generator->enrol_user($student2->id, $maincourse->id, 'student');
        $generator->enrol_user($student1->id, $refcourse->id, 'student');
        $generator->enrol_user($student2->id, $refcourse->id, 'student');

        $subcourse = $generator->create_module('subcourse', [
            'course' => $maincourse->id,
            'refcourse' => $refcourse->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncourse' => 1,
            'completioncoursereversible' => $reversible ? 1 : 0,
        ]);
        $subcourse = $DB->get_record('subcourse', ['id' => $subcourse->id], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('subcourse', $subcourse->id, $maincourse->id);
        rebuild_course_cache($maincourse->id, true);
        $modinfo = get_fast_modinfo($maincourse);
        $cminfo = $modinfo->get_cm($cm->id);

        return [$maincourse, $refcourse, $subcourse, $cminfo, $student1, $student2];
    }

    /**
     * Mark a course complete for a user.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     */
    private function mark_course_complete(int $courseid, int $userid): void {
        $completion = new completion_completion(['course' => $courseid, 'userid' => $userid]);
        $completion->mark_complete();
    }

    /**
     * Mark a course incomplete for a user.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     */
    private function mark_course_incomplete(int $courseid, int $userid): void {
        global $DB;

        $DB->delete_records('course_completions', ['course' => $courseid, 'userid' => $userid]);
        $coursecompletioncache = \cache::make('core', 'coursecompletion');
        $coursecompletioncache->delete($userid . '_' . $courseid);
    }

    /**
     * Get a user's completion state for the subcourse module.
     *
     * @param \stdClass $course Course record.
     * @param \cm_info $cm Course module information.
     * @param int $userid User id.
     * @return int Completion state.
     */
    private function get_module_completion_state(\stdClass $course, \cm_info $cm, int $userid): int {
        $completioncache = \cache::make('core', 'completion');
        $completioncache->delete($userid . '_' . $course->id);

        $completion = new completion_info($course);
        $data = $completion->get_data($cm, false, $userid);

        return (int)$data->completionstate;
    }

    /**
     * Create a subcourse fixture for grade-based completion tests.
     *
     * @param bool $subcoursepassgrade Whether the Subcourse strict pass-grade rule is enabled.
     * @param bool $coregradecompletion Whether Moodle's core receive-grade rule is enabled.
     * @return array Test fixture.
     */
    private function create_grade_completion_fixture(
        bool $subcoursepassgrade = true,
        bool $coregradecompletion = false
    ): array {
        global $DB;

        $generator = $this->getDataGenerator();

        $maincourse = $generator->create_course(['enablecompletion' => 1]);
        $refcourse = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_user();

        $generator->enrol_user($student->id, $maincourse->id, 'student');
        $generator->enrol_user($student->id, $refcourse->id, 'student');

        $subcourse = $generator->create_module('subcourse', [
            'course' => $maincourse->id,
            'refcourse' => $refcourse->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncourse' => 0,
            'completionpassgradesubcourse' => $subcoursepassgrade ? 1 : 0,
        ]);

        $subcourse = $DB->get_record('subcourse', ['id' => $subcourse->id], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('subcourse', $subcourse->id, $maincourse->id, false, MUST_EXIST);
        $DB->set_field('course_modules', 'completionpassgrade', 0, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'completiongradeitemnumber', $coregradecompletion ? 0 : null, ['id' => $cm->id]);

        rebuild_course_cache($maincourse->id, true);
        $modinfo = get_fast_modinfo($maincourse);
        $cminfo = $modinfo->get_cm($cm->id);

        return [$maincourse, $refcourse, $subcourse, $cminfo, $student];
    }

    /**
     * Set the Subcourse activity grade item and optional user grade.
     *
     * @param \stdClass $subcourse Subcourse record.
     * @param int $userid User id.
     * @param float|null $grade User grade, or null for no grade.
     * @param float|null $gradepass Grade to pass.
     */
    private function set_subcourse_grade(\stdClass $subcourse, int $userid, ?float $grade, ?float $gradepass = 100): void {
        $gradeitem = \grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $subcourse->course,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $subcourse->id,
            'itemnumber' => 0,
        ]);

        if (!$gradeitem) {
            subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse, $subcourse->name, true);
            $gradeitem = \grade_item::fetch([
                'source' => 'mod/subcourse',
                'courseid' => $subcourse->course,
                'itemtype' => 'mod',
                'itemmodule' => 'subcourse',
                'iteminstance' => $subcourse->id,
                'itemnumber' => 0,
            ]);
        }

        $gradeitem->gradetype = GRADE_TYPE_VALUE;
        $gradeitem->grademin = 0;
        $gradeitem->grademax = 100;
        $gradeitem->gradepass = $gradepass ?? 0;
        $gradeitem->update('mod/subcourse');

        if ($grade === null) {
            return;
        }

        $gradegrade = \grade_grade::fetch([
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);

        if (!$gradegrade) {
            $gradegrade = new \grade_grade([
                'itemid' => $gradeitem->id,
                'userid' => $userid,
            ], false);
        }

        $gradegrade->rawgrade = $grade;
        $gradegrade->rawgrademin = 0;
        $gradegrade->rawgrademax = 100;
        $gradegrade->finalgrade = $grade;

        if (empty($gradegrade->id)) {
            $gradegrade->insert('mod/subcourse');
        } else {
            $gradegrade->update('mod/subcourse');
        }
    }

    /**
     * Test referenced course completion syncs only the requested users.
     *
     * @covers \mod_subcourse\completion\refcourse_completion_sync::sync_users
     * @covers \mod_subcourse\completion\custom_completion::get_state
     */
    public function test_refcourse_completion_syncs_only_target_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$maincourse, $refcourse, $subcourse, $cm, $student1, $student2] = $this->create_completion_fixture();

        $this->mark_course_complete($refcourse->id, $student1->id);
        $this->assertSame(1, refcourse_completion_sync::sync_users($subcourse, [$student1->id, $student1->id, 0]));

        $this->assertSame(COMPLETION_COMPLETE, $this->get_module_completion_state($maincourse, $cm, $student1->id));
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_module_completion_state($maincourse, $cm, $student2->id));

        $customcompletion = new custom_completion($cm, $student1->id);
        $this->assertSame(COMPLETION_COMPLETE, $customcompletion->get_state('completioncourse'));
    }

    /**
     * Test referenced course completion only reverts when the activity opts in.
     *
     * @covers \mod_subcourse\completion\refcourse_completion_sync::sync_user
     */
    public function test_refcourse_completion_reversal_is_configurable(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$maincourse, $refcourse, $subcourse, $cm, $student1] = $this->create_completion_fixture(false);

        $this->mark_course_complete($refcourse->id, $student1->id);
        refcourse_completion_sync::sync_user($subcourse, $student1->id);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_module_completion_state($maincourse, $cm, $student1->id));

        $this->mark_course_incomplete($refcourse->id, $student1->id);
        refcourse_completion_sync::sync_user($subcourse, $student1->id);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_module_completion_state($maincourse, $cm, $student1->id));

        [$maincourse, $refcourse, $subcourse, $cm, $student1] = $this->create_completion_fixture(true);
        $this->assertEquals(1, $subcourse->completioncoursereversible);

        $this->mark_course_complete($refcourse->id, $student1->id);
        refcourse_completion_sync::sync_user($subcourse, $student1->id);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_module_completion_state($maincourse, $cm, $student1->id));

        $this->mark_course_incomplete($refcourse->id, $student1->id);
        $customcompletion = new custom_completion($cm, $student1->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $customcompletion->get_state('completioncourse'));
        refcourse_completion_sync::sync_user($subcourse, $student1->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $this->get_module_completion_state($maincourse, $cm, $student1->id));
    }

    /**
     * Test active rule descriptions expose the referenced course completion rule.
     *
     * @covers ::subcourse_get_completion_active_rule_descriptions
     */
    public function test_completion_active_rule_descriptions_include_referenced_course_rule(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $cm] = $this->create_completion_fixture();
        rebuild_course_cache($cm->course, true);
        $modinfo = get_fast_modinfo($cm->course);
        $cminfo = $modinfo->get_cm($cm->id);

        $this->assertContains(
            get_string('completioncourse_text', 'subcourse'),
            subcourse_get_completion_active_rule_descriptions($cminfo)
        );
    }

    /**
     * Test the subcourse grade item copies the referenced course grade to pass.
     *
     * @covers ::subcourse_grades_update
     * @covers ::subcourse_get_fetched_item_fields
     */
    public function test_subcourse_grade_item_copies_gradepass(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $maincourse = $generator->create_course();
        $refcourse = $generator->create_course();

        $coursegradeitem = \grade_item::fetch_course_item($refcourse->id);
        $coursegradeitem->gradepass = 75;
        $coursegradeitem->update('test');

        $subcourse = $generator->create_module('subcourse', [
            'course' => $maincourse->id,
            'refcourse' => $refcourse->id,
        ]);

        subcourse_grades_update($maincourse->id, $subcourse->id, $refcourse->id, null, true);

        $subcoursegradeitem = \grade_item::fetch([
            'source' => 'mod/subcourse',
            'courseid' => $maincourse->id,
            'itemtype' => 'mod',
            'itemmodule' => 'subcourse',
            'iteminstance' => $subcourse->id,
            'itemnumber' => 0,
        ]);

        $this->assertEquals(75, $subcoursegradeitem->gradepass);
    }

    /**
     * Test strict Subcourse pass-grade completion states.
     *
     * @covers \mod_subcourse\completion\custom_completion::get_state
     * @dataProvider strict_passgrade_completion_provider
     * @param float|null $grade User grade.
     * @param float|null $gradepass Grade to pass.
     * @param string $expectedstate Expected completion state label.
     */
    public function test_strict_passgrade_completion_states(
        ?float $grade,
        ?float $gradepass,
        string $expectedstate
    ): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [, , $subcourse, $cm, $student] = $this->create_grade_completion_fixture();
        $this->set_subcourse_grade($subcourse, $student->id, $grade, $gradepass);

        $expectedstate = $this->completion_state_from_label($expectedstate);
        $customcompletion = new custom_completion($cm, $student->id);
        $this->assertSame($expectedstate, $customcompletion->get_state('completionpassgradesubcourse'));
    }

    /**
     * Data provider for strict Subcourse pass-grade completion.
     *
     * @return array
     */
    public static function strict_passgrade_completion_provider(): array {
        return [
            'no grade' => [null, 100, 'incomplete'],
            'below grade to pass' => [0, 100, 'failed'],
            'equal to grade to pass' => [100, 100, 'complete'],
            'above grade to pass' => [100, 75, 'complete'],
            'missing grade to pass' => [100, null, 'incomplete'],
        ];
    }

    /**
     * Convert provider-safe completion state labels to Moodle constants.
     *
     * @param string $label Completion state label.
     * @return int Moodle completion state constant.
     */
    private function completion_state_from_label(string $label): int {
        return match ($label) {
            'complete' => COMPLETION_COMPLETE,
            'failed' => COMPLETION_COMPLETE_FAIL,
            'incomplete' => COMPLETION_INCOMPLETE,
            default => throw new \coding_exception('Unknown completion state label: ' . $label),
        };
    }

    /**
     * Test pass-grade-only completion sync evaluates the grade rule without requiring referenced course completion.
     *
     * @covers \mod_subcourse\completion\refcourse_completion_sync::sync_user
     * @covers \mod_subcourse\completion\custom_completion::get_state
     */
    public function test_passgrade_only_sync_does_not_require_referenced_course_completion(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$maincourse, , $subcourse, $cm, $student] = $this->create_grade_completion_fixture();

        $this->set_subcourse_grade($subcourse, $student->id, 100, 75);
        refcourse_completion_sync::sync_user($subcourse, $student->id);
        $this->assertSame(COMPLETION_COMPLETE, $this->get_module_completion_state($maincourse, $cm, $student->id));

        $this->set_subcourse_grade($subcourse, $student->id, 50, 75);
        refcourse_completion_sync::sync_user($subcourse, $student->id);
        $this->assertSame(COMPLETION_COMPLETE_FAIL, $this->get_module_completion_state($maincourse, $cm, $student->id));
    }

    /**
     * Test failing Subcourse pass-grade completion creates Moodle's failed completion state.
     *
     * @covers \mod_subcourse\completion\custom_completion::get_state
     */
    public function test_strict_passgrade_failure_is_complete_fail(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$maincourse, , $subcourse, $cm, $student] = $this->create_grade_completion_fixture();
        $this->set_subcourse_grade($subcourse, $student->id, 0, 100);

        $customcompletion = new custom_completion($cm, $student->id);
        $this->assertSame(COMPLETION_COMPLETE_FAIL, $customcompletion->get_state('completionpassgradesubcourse'));

        refcourse_completion_sync::sync_user($subcourse, $student->id);
        $this->assertSame(COMPLETION_COMPLETE_FAIL, $this->get_module_completion_state($maincourse, $cm, $student->id));
    }

    /**
     * Test receive-grade completion remains independent from strict Subcourse pass-grade completion.
     *
     * @covers \mod_subcourse\completion\custom_completion::get_state
     */
    public function test_any_grade_completion_still_completes_for_failing_grade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$maincourse, , $subcourse, $cm, $student] = $this->create_grade_completion_fixture(false, true);
        $this->set_subcourse_grade($subcourse, $student->id, 0, 100);

        $state = $this->get_module_completion_state($maincourse, $cm, $student->id);
        $this->assertContains($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_FAIL]);
    }
}
