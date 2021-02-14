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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Subcourse module admin settings and defaults
 *
 * @package     mod_subcourse
 * @category    admin
 * @copyright   2020 Arnaud Trouvé <arnaud.trouve@andil.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'subcoursemodeditdefaults',
            get_string('modeditdefaults', 'admin'),
            get_string('condifmodeditdefaults', 'admin')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'mod_subcourse/coursepageprintprogress',
            get_string('settings:coursepageprintprogress', 'mod_subcourse'),
            get_string('settings:coursepageprintprogress_desc', 'mod_subcourse'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'mod_subcourse/coursepageprintgrade',
            get_string('settings:coursepageprintgrade', 'mod_subcourse'),
            get_string('settings:coursepageprintgrade_desc', 'mod_subcourse'),
            1
        ));
    }
}
