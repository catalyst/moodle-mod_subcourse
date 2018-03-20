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

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url(new moodle_url('/mod/subcourse/index.php', array('id' => $id)));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->shortname);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('modulenameplural', 'subcourse'));

$event = \mod_subcourse\event\course_module_instance_list_viewed::create(array(
    'context' => context_course::instance($course->id)
));
$event->add_record_snapshot('course', $course);
$event->trigger();

echo $OUTPUT->header();

if (!$subcourses = get_all_instances_in_course('subcourse', $course)) {
    echo $OUTPUT->heading(get_string('nosubcourses', 'subcourse'), 2);
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
    echo $OUTPUT->footer();
    die();
}

$usesections = course_format_uses_sections($course->format);

$timenow = time();
$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strname = get_string('subcoursename', 'subcourse');
$strdesc = get_string('moduleintro');

$table = new html_table();
$table->id = 'subcourseslist';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strdesc);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strname, $strdesc);
    $table->align = array ('left', 'left');
}

foreach ($subcourses as $subcourse) {
    $attributes = array();
    if (empty($subcourse->visible)) {
        $attributes['class'] = 'dimmed';
    }
    $link = html_writer::link(new moodle_url('/mod/subcourse/view.php',
        array('id' => $subcourse->coursemodule)), format_string($subcourse->name), $attributes);
    $description = format_module_intro('subcourse', $subcourse, $subcourse->coursemodule);
    if ($usesections) {
        $table->data[] = array(get_section_name($course, $subcourse->section), $link, $description);
    } else {
        $table->data[] = array($link, $description);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'subcourse'));
echo html_writer::table($table);
echo $OUTPUT->footer();
