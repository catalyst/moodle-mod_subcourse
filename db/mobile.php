<?php
// This file is part of Moodle - https://moodle.org/
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
 * Declares the Mobile App addons provided by this plugin.
 *
 * @package     mod_subcourse
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_subcourse' => [
        'handlers' => [
            'subcourse' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/subcourse/pix/icon.png',
                    'class' => '',
                ],

                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'main_view',
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_subcourse'],
            ['gotorefcourse', 'mod_subcourse'],
            ['currentgrade', 'mod_subcourse'],
        ],
    ],
];
