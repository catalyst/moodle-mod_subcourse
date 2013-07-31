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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/gradelib.php');

$id = required_param('id', PARAM_INT); // course module id
$fetchnow = optional_param('fetchnow', 0, PARAM_INT); // manual fetch

$cm = get_coursemodule_from_id('subcourse', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$subcourse = $DB->get_record('subcourse', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

$PAGE->set_url(new moodle_url('/mod/subcourse/view.php', array('id' => $cm->id)));
$PAGE->set_title($subcourse->name);
$PAGE->set_heading($course->fullname);

if (!$refcourse = $DB->get_record('course', array('id' => $subcourse->refcourse))) {
    print_error('errinvalidrefcourse', 'subcourse');
}

if ($fetchnow) {
    require_sesskey();
    require_capability('mod/subcourse:fetchgrades', $context);
    add_to_log($course->id, 'subcourse', 'fetch', "view.php?id=$cm->id", $refcourse->id);
    try {
        subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse);
        subcourse_update_timefetched($subcourse->id);
        redirect($CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id);
    } catch (subcourse_localremotescale_exception $e) {
        print_error($e->errorcode, 'subcourse',
                    $CFG->wwwroot.'/mod/subcourse/view.php?id='.$cm->id);
    }
}

add_to_log($course->id, 'subcourse', 'view', "view.php?id=$cm->id", $subcourse->id);

echo $OUTPUT->header();

echo $OUTPUT->heading($subcourse->name);
echo $OUTPUT->box(format_module_intro('subcourse', $subcourse, $cm->id));

$refcourseurl = new moodle_url('/course/view.php', array('id' => $refcourse->id));
$refcourselink = array(
    'name' => $refcourse->fullname,
    'href' => $refcourseurl->out(),
);

echo $OUTPUT->heading(get_string('gotocoursename', 'subcourse', $refcourselink), 3);

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

if (has_capability('gradereport/grader:view', $coursecontext)
        and has_capability('moodle/grade:viewall', $coursecontext)) {
    echo $OUTPUT->single_button(
        new moodle_url('/grade/report/grader/index.php', array('id' => $course->id)),
        get_string('seeallcoursegrades', 'grades'), 'get'
    );
}

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
