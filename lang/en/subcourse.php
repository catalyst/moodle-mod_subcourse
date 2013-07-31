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
 * Defines English strings of the subcourse module
 *
 * @package     mod_subcourse
 * @category    string
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['currentgrade'] = 'Currrent grade:';
$string['errinvalidrefcourse'] = 'Error: Invalid ID of referenced course. The referenced course has been probably deleted.';
$string['errlocalremotescale'] = 'Unable to fetch grades: the remote final grade item uses local scale.';
$string['errnonnumeric'] = 'Non-numeric argument';
$string['fetchnow'] = 'Fetch now';
$string['gotocoursename'] = 'Go to the course <a href="{$a->href}">{$a->name}</a>';
$string['hiddencourse'] = '*hidden*';
$string['lastfetchnever'] = 'The grades have not been fetched yet';
$string['lastfetchtime'] = 'Last fetch: {$a}';
$string['modulename'] = 'Subcourse';
$string['modulename_help'] = 'The module provides very simple yet useful functionality. When added into a course, it behaves as a graded activity. The grade for each student is taken from a final grade in another course. Combined with metacourses, this allows course designers to organize courses into separate units.';
$string['modulenameplural'] = 'Subcourses';
$string['pluginadministration'] = 'Subcourse administration';
$string['pluginname'] = 'Subcourse';
$string['refcourse'] = 'Referenced course';
$string['refcourse_help'] = 'The referenced course is the one the grade of the activity is taken from. Students should be enroled into the referenced course.';
$string['refcourselabel'] = 'Take grades from';
$string['subcourseintro'] = 'Description';
$string['subcoursename'] = 'Title';
