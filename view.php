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

$PAGE->set_url(new moodle_url('/mod/subcourse/view.php', array('id' => $cm->id)));

if (empty($subcourse->refcourse)) {
    $refcourse = false;
} else {
    $refcourse = $DB->get_record('course', array('id' => $subcourse->refcourse), '*', IGNORE_MISSING);
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

if ($refcourse) {
    redirect(new moodle_url('/course/view.php', array('id' => $subcourse->refcourse)));
} else {
    print_error('refcoursenull', 'subcourse', new moodle_url('/course/view.php', array('id' => $cm->course)));
}
