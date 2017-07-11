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

/**
 * View a particular instance of the subcourse
 *
 * @package     mod_subcourse
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/subcourse/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = required_param('id', PARAM_INT);
$fetchnow = optional_param('fetchnow', 0, PARAM_INT);

$cm = get_coursemodule_from_id('subcourse', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$subcourse = $DB->get_record('subcourse', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

$PAGE->set_url(new moodle_url('/mod/subcourse/view.php', array('id' => $cm->id)));
$PAGE->set_title($subcourse->name);
$PAGE->set_heading($course->fullname);

if (empty($subcourse->refcourse)) {
    $refcourse = false;

} else {
    $refcourse = $DB->get_record('course', array('id' => $subcourse->refcourse), '*', IGNORE_MISSING);
}

if ($fetchnow and $refcourse) {
    require_sesskey();
    require_capability('mod/subcourse:fetchgrades', $context);
    $event = \mod_subcourse\event\subcourse_grades_fetched::create(array(
        'objectid' => $subcourse->id,
        'context' => $context,
        'other' => array('refcourse' => $refcourse->id)
    ));
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('subcourse', $subcourse);
    $event->trigger();
    $result = subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
    if ($result == GRADE_UPDATE_OK) {
        subcourse_update_timefetched($subcourse->id);
        redirect(new moodle_url('/mod/subcourse/view.php', array('id' => $cm->id)));
    } else {
        print_error('errfetch', 'subcourse', $CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id, $result);
    }
}

$event = \mod_subcourse\event\course_module_viewed::create(array(
    'objectid' => $subcourse->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('subcourse', $subcourse);
$event->trigger();

if ($refcourse and !empty($subcourse->instantredirect)) {
    if (!has_capability('mod/subcourse:fetchgrades', $context)) {
        redirect(new moodle_url('/course/view.php', array('id' => $refcourse->id)));
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading($subcourse->name);
echo $OUTPUT->box(format_module_intro('subcourse', $subcourse, $cm->id));

if ($refcourse) {
    $refcourseurl = new moodle_url('/course/view.php', array('id' => $refcourse->id));
    $refcourselink = array(
        'name' => $refcourse->fullname,
        'href' => $refcourseurl->out(),
    );

    echo $OUTPUT->heading(get_string('gotocoursename', 'subcourse', $refcourselink), 3);

    echo $OUTPUT->box_start('generalbox', 'gradeinfobox');

    $currentgrade = grade_get_grades($subcourse->course, 'mod', 'subcourse', $subcourse->id, $USER->id);
    if (!empty($currentgrade->items[0]->grades)) {
        $currentgrade = reset($currentgrade->items[0]->grades);
        if (isset($currentgrade->grade) and !($currentgrade->hidden)) {
            $strgrade = $currentgrade->str_grade;
            echo $OUTPUT->container(get_string('currentgrade', 'subcourse', $strgrade), 'currentgrade');
        }
    }

    if (has_capability('gradereport/grader:view', $coursecontext)
            and has_capability('moodle/grade:viewall', $coursecontext)) {
        echo $OUTPUT->single_button(
            new moodle_url('/grade/report/grader/index.php', array('id' => $course->id)),
            get_string('seeallcoursegrades', 'grades'), 'get'
        );
    }

    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start('generalbox', 'fetchinfobox');

    if (empty($subcourse->timefetched)) {
        echo get_string('lastfetchnever', 'subcourse');
    } else {
        echo get_string('lastfetchtime', 'subcourse', userdate($subcourse->timefetched));
    }

    if (has_capability('mod/subcourse:fetchgrades', $context)) {
        echo $OUTPUT->single_button(
            new moodle_url($PAGE->url, array('sesskey' => sesskey(), 'fetchnow' => 1)),
            get_string('fetchnow', 'subcourse')
        );
    }

    echo $OUTPUT->box_end();

} else {
    if (has_capability('mod/subcourse:fetchgrades', $context)) {
        echo $OUTPUT->notification(get_string('refcoursenull', 'subcourse'));
    }
}

echo $OUTPUT->footer();
