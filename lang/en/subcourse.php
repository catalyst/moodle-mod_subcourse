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

$string['blankwindow'] = 'Open in a new window';
$string['blankwindow_help'] = 'When selected, the link will open the referenced course in a new browser window.';
$string['currentgrade'] = 'Current grade: {$a}';
$string['currentprogress'] = 'Progress: {$a}%';
$string['errfetch'] = 'Unable to fetch grades: error code {$a}';
$string['errlocalremotescale'] = 'Unable to fetch grades: the remote final grade item uses local scale.';
$string['eventgradesfetched'] = 'Grades fetched';
$string['fetchnow'] = 'Fetch grades now';
$string['gotorefcourse'] = 'Go to {$a}';
$string['gotorefcoursegrader'] = 'All grades in {$a}';
$string['gotorefcoursemygrades'] = 'My grades in {$a}';
$string['hiddencourse'] = '*hidden*';
$string['instantredirect'] = 'Redirect to the referenced course';
$string['instantredirect_help'] = 'If enabled, users will be redirected to the referenced course when attempting to view the subcourse module page. Does not affect users with the permission to fetch grades manually.';
$string['lastfetchnever'] = 'The grades have not been fetched yet';
$string['lastfetchtime'] = 'Last fetch: {$a}';
$string['modulename'] = 'Subcourse';
$string['modulename_help'] = 'The module provides very simple yet useful functionality. When added into a course, it behaves as a graded activity. The grade for each student is taken from a final grade in another course. Combined with metacourses, this allows course designers to organize courses into separate units.';
$string['modulenameplural'] = 'Subcourses';
$string['nocoursesavailable'] = 'No courses you could fetch grades from';
$string['nosubcourses'] = 'There are no subcourses in this course';
$string['pluginadministration'] = 'Subcourse administration';
$string['pluginname'] = 'Subcourse';
$string['privacy:metadata'] = 'Subcourse does not store any personal data';
$string['refcourse'] = 'Referenced course';
$string['refcourse_help'] = 'The referenced course is the one the grade of the activity is taken from. Students should be enroled into the referenced course.

You need to be a teacher in the course to have it listed here. You may want to ask your site administrator to set up this subcourse for you to fetch grades from other courses.';
$string['refcoursecurrent'] = 'Keep current reference';
$string['refcourselabel'] = 'Fetch grades from';
$string['refcoursenull'] = 'No referenced course configured';
$string['subcourse:addinstance'] = 'Add a new subcourse';
$string['subcourse:begraded'] = 'Receive grade from the referenced course';
$string['subcourse:fetchgrades'] = 'Fetch grades manually from the referenced course';
$string['subcoursename'] = 'Subcourse name';
$string['taskcheckcompletedrefcourses'] = 'Check referenced courses completion';
$string['taskfetchgrades'] = 'Fetch subcourse grades';
$string['completioncourse'] = 'Require course completed';
$string['completioncourse_help'] = 'If enabled, the activity is considered complete when a student completes the referenced course.';
$string['completioncourse_text'] = 'Student must complete the referenced course to complete this activity.';

// Deprecated and no longer used.
$string['gotocoursename'] = 'Go to the course <a href="{$a->href}">{$a->name}</a>';
