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

echo $OUTPUT->heading(format_string($subcourse->name));
echo $OUTPUT->box(format_module_intro('subcourse', $subcourse, $cm->id));

if ($refcourse) {
    $percentage = \core_completion\progress::get_course_progress_percentage($refcourse);

    echo html_writer::start_div('container-fluid');
    echo html_writer::start_div('row-fluid');

    if ($percentage !== null) {
        $percentage = floor($percentage);
        echo html_writer::start_div('col-md-6 span6');
        echo html_writer::start_div('subcourseinfo subcourseinfo-progress');
        echo html_writer::div(get_string('currentprogress', 'subcourse', $percentage), 'infotext');
        echo html_writer::start_div('subcourse-progress-bar');
        echo html_writer::div('', '', ['style' => 'width: '.$percentage.'%']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    $currentgrade = grade_get_grades($subcourse->course, 'mod', 'subcourse', $subcourse->id, $USER->id);
    $hasgrade = false;

    if (!empty($currentgrade->items[0]->grades)) {
        $currentgrade = reset($currentgrade->items[0]->grades);

        if (isset($currentgrade->grade) && !($currentgrade->hidden)) {
            $hasgrade = true;
            $strgrade = $currentgrade->str_grade;
            echo html_writer::start_div('col-md-6 span6');
            echo html_writer::start_div('subcourseinfo subcourseinfo-grade');
            echo html_writer::div(get_string('currentgrade', 'subcourse', $strgrade), 'infotext');
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
    }

    echo html_writer::end_div();

    echo html_writer::start_div('row-fluid');
    echo html_writer::start_div('col-md-12 span12');
    echo html_writer::start_div('actionbuttons');

    echo html_writer::link(
        new moodle_url('/course/view.php', ['id' => $refcourse->id]),
        get_string('gotorefcourse', 'subcourse', format_string($refcourse->fullname)),
        ['class' => 'btn btn-primary']
    );

    $refcoursecontext = context_course::instance($refcourse->id);

    if (has_all_capabilities(['gradereport/grader:view', 'moodle/grade:viewall'], $refcoursecontext)) {
        echo html_writer::link(
            new moodle_url('/grade/report/grader/index.php', ['id' => $refcourse->id]),
            get_string('gotorefcoursegrader', 'subcourse', format_string($refcourse->fullname)),
            ['class' => 'btn btn-secondary']
        );
    }

    if (has_all_capabilities(['gradereport/user:view', 'moodle/grade:view'], $refcoursecontext)
            && $refcourse->showgrades && $hasgrade) {
        echo html_writer::link(
            new moodle_url('/grade/report/user/index.php', ['id' => $refcourse->id]),
            get_string('gotorefcoursemygrades', 'subcourse', format_string($refcourse->fullname)),
            ['class' => 'btn btn-secondary']
        );
    }

    if (has_capability('mod/subcourse:fetchgrades', $context)) {
        echo html_writer::link(
            new moodle_url($PAGE->url, ['sesskey' => sesskey(), 'fetchnow' => 1]),
            get_string('fetchnow', 'subcourse'),
            ['class' => 'btn btn-link']
        );

        if (empty($subcourse->timefetched)) {
            $fetchinfo = get_string('lastfetchnever', 'subcourse');
        } else {
            $fetchinfo = get_string('lastfetchtime', 'subcourse', userdate($subcourse->timefetched));
        }

        echo html_writer::tag('small', $fetchinfo, ['class' => 'dimmed_text']);
    }

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();

} else {
    if (has_capability('mod/subcourse:fetchgrades', $context)) {
        echo $OUTPUT->notification(get_string('refcoursenull', 'subcourse'));
    }
}

echo $OUTPUT->footer();
