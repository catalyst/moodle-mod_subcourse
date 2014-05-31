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
 * The mod_resource course module viewed event.
 *
 * @package    mod_subcourse
 * @copyright  2014 Vadim Dvorovenko <vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_subcourse course module viewed event class.
 *
 * @package    mod_resource
 * @since      Moodle 2.7
 * @copyright  2014 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subcourse_grades_fetched extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'subcourse';
    }
 
    public static function get_name() {
        return get_string('eventgradesfetched', 'subcourse');
    }
 
    public function get_description() {
        return "The user with id '$this->userid' fetched grades from subcourse in course module id '$this->contextinstanceid'.";
    }
 
    public function get_url() {
        return new \moodle_url("/mod/$this->objecttable/view.php", array('id' => $this->contextinstanceid));
    }
 
    public function get_legacy_logdata() {
        return array($this->courseid, $this->objecttable, 'fetch', 'view.php?id=' . $this->contextinstanceid, $this->other['refcourse'], $this->contextinstanceid);
    }
 
}
