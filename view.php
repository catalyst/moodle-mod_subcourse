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
    if (!$cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (!$course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    if (!$subcourse = get_record("subcourse", "id", $cm->instance)) {
        error("Course module is incorrect");
    }

} else {
    if (!$subcourse = get_record("subcourse", "id", $a)) {
        error("Course module is incorrect");
    }
    if (!$course = get_record("course", "id", $subcourse->course)) {
        error("Course is misconfigured");
    }
    if (!$cm = get_coursemodule_from_instance("subcourse", $subcourse->id, $course->id)) {
        error("Course Module ID was incorrect");
    }
}

require_login($course->id);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$course_context = get_context_instance(CONTEXT_COURSE, $course->id);

if (!$refcourse = get_record("course", "id", $subcourse->refcourse)) {
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

$navlinks = array();
$navlinks[] = array('name' => $strsubcourses,
                    'link' => "index.php?id=$course->id",
                    'type' => 'activity');
$navlinks[] = array('name' => format_string($subcourse->name),
                    'link' => '',
                    'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($subcourse->name), "", $navigation, "", "", true,
                    update_module_button($cm->id, $course->id, $strsubcourse),
                    navmenu($course, $cm));

if (has_capability('gradereport/grader:view', $course_context)
    && has_capability('moodle/grade:viewall', $course_context)) {

    echo '<div class="allcoursegrades"><a href="'.$CFG->wwwroot.
         '/grade/report/grader/index.php?id='.$course->id.'">'.
         get_string('seeallcoursegrades', 'grades').'</a></div>';
}

print_heading($subcourse->name);
print_box(format_text($subcourse->intro));

$refcourselink = new stdClass();
$refcourselink->name = $refcourse->fullname;
$refcourselink->href = $CFG->wwwroot.'/course/view.php?id='.$refcourse->id;


print_heading(get_string('gotocoursename', 'subcourse', $refcourselink), '', 3);
print_box_start('generalbox', 'fetchinfobox');
if (empty($subcourse->timefetched)) {
    print_string('lastfetchnever', 'subcourse');
} else {
    print_string('lastfetchtime', 'subcourse', userdate($subcourse->timefetched));
}
echo "<form action=\"$CFG->wwwroot/mod/subcourse/view.php?id=$cm->id\" method=\"post\">";
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="fetchnow" value="1" />';
echo '<input type="submit" value="'.get_string('fetchnow', 'subcourse').'" />';
echo "</form>";
print_box_end();

/// Finish the page
print_footer($course);
