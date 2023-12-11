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
 * Provides {@see \mod_subcourse\output\mobile} class.
 *
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_subcourse\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/subcourse/locallib.php');

/**
 * Controls the display of the plugin in the Mobile App.
 *
 * @package   mod_subcourse
 * @category  output
 * @copyright 2020 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Return the data for the CoreCourseModuleDelegate delegate.
     *
     * @param object $args
     * @return object
     */
    public static function main_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';
        $cm = get_coursemodule_from_id('subcourse', $args->cmid);
        $context = \context_module::instance($cm->id);

        require_login($args->courseid, false, $cm, true, true);
        require_capability('mod/subcourse:view', $context);

        $subcourse = $DB->get_record('subcourse', ['id' => $cm->instance], '*', MUST_EXIST);

        $warning = null;
        $progress = null;

        if (empty($subcourse->refcourse)) {
            $refcourse = false;

            if (has_capability('mod/subcourse:fetchgrades', $context)) {
                $warning = get_string('refcoursenull', 'subcourse');
            }

        } else {
            $refcourse = $DB->get_record('course', ['id' => $subcourse->refcourse], 'id, fullname', IGNORE_MISSING);
        }

        if ($refcourse) {
            $refcourse->fullname = \core_external\util::format_string($refcourse->fullname, $context);
            $refcourse->url = new \moodle_url('/course/view.php', ['id' => $refcourse->id]);
            $progress = \core_completion\progress::get_course_progress_percentage($refcourse);
        }

        $currentgrade = subcourse_get_current_grade($subcourse, $USER->id);

        // Pre-format some of the texts for the mobile app.
        $subcourse->name = \core_external\util::format_string($subcourse->name, $context);
        [$subcourse->intro, $subcourse->introformat] = \core_external\util::format_text($subcourse->intro, $subcourse->introformat, $context,
            'mod_subcourse', 'intro');

        $data = [
            'cmid' => $cm->id,
            'subcourse' => $subcourse,
            'refcourse' => $refcourse,
            'progress' => $progress,
            'hasprogress' => isset($progress),
            'currentgrade' => $currentgrade,
            'hasgrade' => isset($currentgrade),
            'warning' => $warning,
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("mod_subcourse/mobile_view_$versionname", $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }
}
