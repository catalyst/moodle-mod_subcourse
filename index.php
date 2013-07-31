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
 * View all instances of subcourse in a particular course
 *
 * @package     mod_subcourse
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course id

if (! $course = $DB->get_record("course", array("id" => $id))) {
    error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "subcourse", "view all", "index.php?id=$course->id", "");


/// Get all required stringssubcourse

$strsubcourses = get_string("modulenameplural", "subcourse");
$strsubcourse  = get_string("modulename", "subcourse");

$page_url = new moodle_url('/mod/subcourse/index.php', array('id' => $id));
$PAGE->set_url($page_url);
$PAGE->set_title($strsubcourses);

/// Get all the appropriate data

if (! $subcourses = get_all_instances_in_course("subcourse", $course)) {
    notice("There are no subcourses", "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

$table = new html_table();

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname);
    $table->align = array ("center", "left");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ("center", "left", "left", "left");
} else {
    $table->head  = array ($strname);
    $table->align = array ("left", "left", "left");
}

foreach ($subcourses as $subcourse) {
    $attributes = array();
    if (!$subcourse->visible) {
        //Show dimmed if the mod is hidden
        $attributes['class'] = 'dimmed';
    }
    $link = html_writer::link('view.php?id='.$subcourse->coursemodule, $subcourse->name,
                              $attributes);

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array ($subcourse->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo $OUTPUT->header();

echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();

