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
 * Library of functions, classes and constants for module subcourse
 *
 * @package     mod_subcourse
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/deprecatedlib.php');

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function subcourse_supports($feature) {

    if (defined('FEATURE_MOD_PURPOSE')) {
        if ($feature === FEATURE_MOD_PURPOSE) {
            return MOD_PURPOSE_CONTENT;
        }
    }

    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will create a new instance and return the id number of the new
 * instance.
 *
 * @param stdClass $subcourse
 * @return int The id of the newly inserted subcourse record
 */
function subcourse_add_instance(stdClass $subcourse) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/subcourse/locallib.php');

    $subcourse->timecreated = time();

    if (empty($subcourse->instantredirect)) {
        $subcourse->instantredirect = 0;
    }

    if (empty($subcourse->blankwindow)) {
        $subcourse->blankwindow = 0;
    }

    if (empty($subcourse->coursepageprintprogress)) {
        $subcourse->coursepageprintprogress = 0;
    }

    if (empty($subcourse->coursepageprintgrade)) {
        $subcourse->coursepageprintgrade = 0;
    }

    $newid = $DB->insert_record("subcourse", $subcourse);

    if (!empty($subcourse->refcourse)) {
        // Create grade_item but do not fetch grades.
        // The context does not exist yet and we can't get users by capability.
        subcourse_grades_update($subcourse->course, $newid, $subcourse->refcourse, $subcourse->name, true);
    }

    if (!empty($subcourse->completionexpected)) {
        \core_completion\api::update_completion_date_event($subcourse->coursemodule, 'subcourse', $newid,
            $subcourse->completionexpected);
    }

    return $newid;
}

/**
 * Given an object containing all the necessary data, (defined by the form)
 * this function will update an existing instance with new data.
 *
 * @param stdClass $subcourse
 * @return boolean success/failure
 */
function subcourse_update_instance(stdClass $subcourse) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/subcourse/locallib.php');

    $cmid = $subcourse->coursemodule;

    $subcourse->timemodified = time();
    $subcourse->id = $subcourse->instance;

    if (!empty($subcourse->refcoursecurrent)) {
        unset($subcourse->refcourse);
    }

    if (empty($subcourse->instantredirect)) {
        $subcourse->instantredirect = 0;
    }

    if (empty($subcourse->blankwindow)) {
        $subcourse->blankwindow = 0;
    }

    if (empty($subcourse->coursepageprintprogress)) {
        $subcourse->coursepageprintprogress = 0;
    }

    if (empty($subcourse->coursepageprintgrade)) {
        $subcourse->coursepageprintgrade = 0;
    }

    $DB->update_record('subcourse', $subcourse);

    if (!empty($subcourse->refcourse)) {
        if (has_capability('mod/subcourse:fetchgrades', context_module::instance($cmid))) {
            subcourse_grades_update($subcourse->course, $subcourse->id, $subcourse->refcourse, $subcourse->name,
                false, false, [], $subcourse->fetchpercentage);
            subcourse_update_timefetched($subcourse->id);
        }
    }

    \core_completion\api::update_completion_date_event($cmid, 'subcourse', $subcourse->id, $subcourse->completionexpected);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean success/failure
 */
function subcourse_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Check the instance exists.
    if (!$subcourse = $DB->get_record("subcourse", ["id" => $id])) {
        return false;
    }

    // Remove the instance record.
    $DB->delete_records("subcourse", ["id" => $subcourse->id]);

    // Clean up the gradebook items.
    grade_update('mod/subcourse', $subcourse->course, 'mod', 'subcourse', $subcourse->id, 0, null, ['deleted' => true]);

    return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of subcourse. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $subcourseid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function subcourse_get_participants($subcourseid) {
    return false;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record.
 * @param stdClass $user The user record.
 * @param cm_info|stdClass $mod The course module info object or record.
 * @param stdClass $subcourse The subcourse instance record.
 * @return null
 */
function subcourse_user_outline($course, $user, $mod, $subcourse) {
    return true;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course The course record.
 * @param stdClass $user The user record.
 * @param cm_info|stdClass $mod The course module info object or record.
 * @param stdClass $subcourse The subcourse instance record.
 * @return boolean
 */
function subcourse_user_complete($course, $user, $mod, $subcourse) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in subcourse activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean true if anything was printed, otherwise false
 */
function subcourse_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Is a scale used by the given subcourse instance?
 *
 * The subcourse itself does not generate grades so we always return
 * false here in order not to block the scale removal.
 *
 * @param int $subcourseid id of an instance of this module
 * @param int $scaleid
 * @return bool
 */
function subcourse_scale_used($subcourseid, $scaleid) {
    return false;
}

/**
 * Is a scale used by some subcourse instance?
 *
 * The subcourse itself does not generate grades so we always return
 * false here in order not to block the scale removal.
 *
 * @param int $scaleid
 * @return boolean True if the scale is used by any subcourse
 */
function subcourse_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * This will provide summary info about the user's grade in the subcourse below the link on
 * the course/view.php page
 *
 * @param cm_info $cm
 * @return void
 */
function mod_subcourse_cm_info_view(cm_info $cm) {
    global $CFG, $USER, $DB;

    if (isset($cm->customdata->coursepageprintgrade) && isset($cm->customdata->coursepageprintprogress)) {
        $displayoptions = (object) [
            'coursepageprintgrade' => $cm->customdata->coursepageprintgrade,
            'coursepageprintprogress' => $cm->customdata->coursepageprintprogress,
        ];

    } else {
        // This is unexpected - the customdata should be set in {@see subcourse_get_coursemodule_info()}.
        $displayoptions = $DB->get_record('subcourse', ['id' => $cm->instance], 'coursepageprintgrade, coursepageprintprogress');
    }

    $html = '';

    if ($displayoptions->coursepageprintprogress) {
        $sql = "SELECT r.*
                  FROM {course} r
                  JOIN {subcourse} s ON s.refcourse = r.id
                 WHERE s.id = :subcourseid";

        $refcourse = $DB->get_record_sql($sql, ['subcourseid' => $cm->instance], IGNORE_MISSING);
        $percentage = null;
        if ($refcourse) {
            $percentage = \core_completion\progress::get_course_progress_percentage($refcourse);
        }
        if ($percentage !== null) {
            $percentage = floor($percentage);
            $html .= html_writer::tag('div', get_string('currentprogress', 'subcourse', $percentage),
                ['class' => 'contentafterlink']);
        }
    }

    if ($displayoptions->coursepageprintgrade) {
        require_once($CFG->libdir.'/gradelib.php');

        $grades = grade_get_grades($cm->course, 'mod', 'subcourse', $cm->instance, $USER->id);
        $currentgrade = (empty($grades->items[0]->grades)) ? null : reset($grades->items[0]->grades);

        if (($currentgrade !== null) and isset($currentgrade->grade) and !($currentgrade->hidden)) {
            $strgrade = $currentgrade->str_grade;
            $html .= html_writer::tag('div', get_string('currentgrade', 'subcourse', $strgrade),
                ['class' => 'contentafterlink']);
        }
    }

    if ($html !== '') {
        $cm->set_after_link($html);
    }
}

/**
 * Return the action associated with the given calendar event, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_subcourse_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory) {

    $cm = get_fast_modinfo($event->courseid)->instances['subcourse'][$event->instance];

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/subcourse/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@see get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function subcourse_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $subcourse = $DB->get_record('subcourse', ['id' => $coursemodule->instance],
        'id, name, intro, introformat, instantredirect, blankwindow, coursepageprintgrade, coursepageprintprogress');

    if (!$subcourse) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $subcourse->name;
    $info->customdata = (object) [
        'coursepageprintgrade' => $subcourse->coursepageprintgrade,
        'coursepageprintprogress' => $subcourse->coursepageprintprogress,
    ];

    if ($subcourse->instantredirect && $subcourse->blankwindow) {
        $url = new moodle_url('/mod/subcourse/view.php', ['id' => $coursemodule->id, 'isblankwindow' => 1]);
        $info->onclick = "window.open('".$url->out(false)."'); return false;";
    }

    if ($coursemodule->showdescription) {
        // Set content from intro and introformat. Filters are disabled because we filter with format_text at display time.
        $info->content = format_module_intro('subcourse', $subcourse, $coursemodule->id, false);
    }

    return $info;
}
