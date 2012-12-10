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
 * This page prints a particular instance of subcourse
 *
 * @author David Mudrak <david.mudrak@gmail.com>
 * @version $Id$
 * @package mod/subcourse
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$a = optional_param('a', 0, PARAM_INT); // subcourse ID
$fetchnow = optional_param('fetchnow', 0, PARAM_INT); // manual fetch

if ($id) {
    if (!$cm = $DB->get_record("course_modules", array("id" => $id))) {
        error("Course Module ID was incorrect");
    }

    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        error("Course is misconfigured");
    }

    if (!$subcourse = $DB->get_record("subcourse", array("id" => $cm->instance))) {
        error("Course module is incorrect");
    }

} else {
    if (!$subcourse = $DB->get_record("subcourse", array("id" => $a))) {
        error("Course module is incorrect");
    }
    if (!$course = $DB->get_record("course", array("id" => $subcourse->course))) {
        error("Course is misconfigured");
    }
    if (!$cm = get_coursemodule_from_instance("subcourse", $subcourse->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

require_login($course->id);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$course_context = get_context_instance(CONTEXT_COURSE, $course->id);

if (!$refcourse = $DB->get_record("course", array("id" => $subcourse->refcourse))) {
    print_error("errinvalidrefcourse", "subcourse");
}

if ($fetchnow) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
    require_capability('mod/subcourse:fetchgrades', $context);
    add_to_log($course->id, "subcourse", "fetch", "view.php?id=$cm->id", "$refcourse->id");
    try {
        subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
        subcourse_update_timefetched($subcourse->id);
        redirect($CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id);
    } catch (subcourse_localremotescale_exception $e) {
        print_error($e->errorcode, 'subcourse',
                    $CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id);
    }
}

add_to_log($course->id, "subcourse", "view", "view.php?id=$cm->id", "$subcourse->id");

/// Print the page header
$strsubcourses = get_string("modulenameplural", "subcourse");
$strsubcourse = get_string("modulename", "subcourse");

$page_url = new moodle_url('/mod/subcourse/index.php', array('id' => $course->id));
$PAGE->set_url($page_url);
$PAGE->set_title(format_string($subcourse->name));
$PAGE->set_heading($course->shortname);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'subcourse'));

echo $OUTPUT->header();

if (has_capability('gradereport/grader:view', $course_context)
    && has_capability('moodle/grade:viewall', $course_context)) {

    echo html_writer::start_tag('div', array('class' => 'allcoursegrades'));
    echo html_writer::link($CFG->wwwroot.'/grade/report/grader/index.php?id='.$course->id,
                           get_string('seeallcoursegrades', 'grades'));
    echo html_writer::end_tag('div');
}

echo $OUTPUT->heading($subcourse->name);
echo $OUTPUT->box(format_text($subcourse->intro));

$refcourselink = new stdClass();
$refcourselink->name = $refcourse->fullname;
$refcourselink->href = $CFG->wwwroot.'/course/view.php?id='.$refcourse->id;

echo $OUTPUT->heading(get_string('gotocoursename', 'subcourse', $refcourselink), 3);
echo $OUTPUT->box_start('generalbox', 'fetchinfobox');
if (empty($subcourse->timefetched)) {
    print_string('lastfetchnever', 'subcourse');
} else {
    print_string('lastfetchtime', 'subcourse', userdate($subcourse->timefetched));
}

echo html_writer::start_tag('form', array('action' => $CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id,
                                          'method' => 'post',));
echo html_writer::empty_tag('input', array('type' => 'hidden',
                                           'name' => 'sesskey',
                                           'value' => sesskey()));
echo html_writer::empty_tag('input', array('type' => 'hidden',
                                           'name' => 'fetchnow',
                                           'value' => 1));
echo html_writer::empty_tag('input', array('type' => 'submit',
                                           'value' => get_string('fetchnow', 'subcourse')));
echo html_writer::end_tag('form');

echo $OUTPUT->box_end();

/// Finish the page
echo $OUTPUT->footer();
