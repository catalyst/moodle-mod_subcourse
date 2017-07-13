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
 * @package     mod_subcourse
 * @category    backup
 * @copyright   2013 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete subcourse structure for backup
 */
class backup_subcourse_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the complete subcourse structure for backup
     */
    protected function define_structure() {

        $subcourse = new backup_nested_element('subcourse', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'timefetched',
            'refcourse', 'instantredirect', 'completioncourse'
        ));

        $subcourse->set_source_table('subcourse', array('id' => backup::VAR_ACTIVITYID));

        return $this->prepare_activity_structure($subcourse);
    }
}
