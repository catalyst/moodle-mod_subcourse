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
 * Defines the main subcourse settings form
 *
 * @package     mod_subcourse
 * @category    form
 * @copyright   2008 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/coursecatlib.php');

/**
 * Subcourse settings form
 */
class mod_subcourse_mod_form extends moodleform_mod {

    /**
     * Form fields definition
     */
    public function definition() {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        // General -------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('subcoursename', 'subcourse'),
                           array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor();

        // Referenced course ---------------------------------------------------
        $mform->addElement('header', 'section-refcourse', get_string('refcourse', 'subcourse'));
        $mform->setExpanded('section-refcourse');
        $mform->addHelpButton('section-refcourse', 'refcourse', 'subcourse');

        $mycourses = subcourse_available_courses();

        $currentrefcourseid = isset($this->current->refcourse) ? $this->current->refcourse : null;
        $currentrefcoursename = null;
        $currentrefcourseavailable = false;

        if (!empty($currentrefcourseid)) {

            if ($currentrefcourseid == $COURSE->id) {
                // Invalid self-reference.
                $this->current->refcourse = 0;
                $includenoref = true;

            } else {
                $currentrefcoursename = $DB->get_field('course', 'fullname', array('id' => $currentrefcourseid), IGNORE_MISSING);
            }

            if ($currentrefcoursename === false) {
                // Reference to non-existing course.
                $this->current->refcourse = 0;
                $includenoref = true;

            } else {
                // Check if the currently set value is still available.
                foreach ($mycourses as $mycourse) {
                    if ($mycourse->id == $currentrefcourseid) {
                        $currentrefcourseavailable = true;
                        break;
                    }
                }
            }
        }

        if (!empty($currentrefcourseid) and !$currentrefcourseavailable) {
            // Currently referring to a course that is not available for us (e.g. the admin
            // has set up this Subcourse for the teacher or the teacher lost his role in the referred
            // course etc. Give them a chance to just keep such a reference.
            $mform->addElement('checkbox', 'refcoursecurrent', get_string('refcoursecurrent', 'subcourse'),
                format_string($currentrefcoursename));
            $mform->setDefault('refcoursecurrent', 1);
            $includekeepref = true;
        }

        $options = array();

        if (empty($mycourses)) {
            if (empty($includekeepref)) {
                $options = array(0 => get_string('nocoursesavailable', 'subcourse'));
                $mform->addElement('select', 'refcourse', get_string('refcourselabel', 'subcourse'), $options);
            } else {
                $mform->addElement('hidden', 'refcourse', 0);
                $mform->setType('refcourse', PARAM_INT);
            }

        } else {
            $catlist = coursecat::make_categories_list('', 0, ' / ');
            foreach ($mycourses as $mycourse) {
                if (empty($options[$catlist[$mycourse->category]])) {
                    $options[$catlist[$mycourse->category]] = array();
                }
                $courselabel = $mycourse->fullname.' ('.$mycourse->shortname.')';
                $options[$catlist[$mycourse->category]][$mycourse->id] = $courselabel;
                if (empty($mycourse->visible)) {
                    $hiddenlabel = ' '.get_string('hiddencourse', 'subcourse');
                    $options[$catlist[$mycourse->category]][$mycourse->id] .= $hiddenlabel;
                }
            }
            if (!empty($includenoref)) {
                $options['---'] = array(0 => get_string('none'));
            }

            $mform->addElement('selectgroups', 'refcourse', get_string('refcourselabel', 'subcourse'), $options);

            if (!empty($includekeepref)) {
                $mform->disabledIf('refcourse', 'refcoursecurrent', 'checked');
            }
        }


        // Common module settings ----------------------------------------------
        $this->standard_coursemodule_elements();

        // Common action buttons
        $this->add_action_buttons();
    }
}

