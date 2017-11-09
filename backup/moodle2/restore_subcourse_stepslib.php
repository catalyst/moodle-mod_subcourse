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
 * Provides {@link restore_subcourse_activity_structure_step} class
 *
 * @package     mod_subcourse
 * @category    backup
 * @copyright   2013 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one subcourse activity
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_subcourse_activity_structure_step extends restore_activity_structure_step {

    /**
     * Attaches the handlers of the backup XML tree parts.
     *
     * @return array of restore_path_element
     */
    protected function define_structure() {

        $paths = array();

        $paths[] = new restore_path_element('subcourse', '/activity/subcourse');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the /activity/subcourse path element.
     *
     * @param object|array $data node contents
     */
    protected function process_subcourse($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timefetched = 0;

        if (!$this->task->is_samesite() or !$DB->record_exists('course', array('id' => $data->refcourse))) {
            $data->refcourse = 0;
        }

        $newitemid = $DB->insert_record('subcourse', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Callback to be executed after the restore.
     */
    protected function after_execute() {
        // Add subcourse related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_subcourse', 'intro', null);
    }
}
