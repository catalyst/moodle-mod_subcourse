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

/**
 * Subcourse settings form
 */
class mod_subcourse_mod_form extends moodleform_mod {

    /**
     * Form fields definition
     */
    public function definition() {
        global $CFG, $DB;

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
        $mform->addElement('header', 'subcoursefieldset', get_string('refcourse', 'subcourse'));

        $mycourses = subcourse_available_courses();

        if (empty($mycourses)) {
            if (empty($this->current->refcourse)) {
                $mform->addElement('html', get_string('nocoursesavailable', 'subcourse'));
            } else {
                $current = $DB->get_field('course', 'fullname', array('id' => $this->current->refcourse));
                $mform->addElement('static', 'refcoursestatic', get_string('refcourselabel', 'subcourse'),
                    format_string($current));
                $mform->addHelpButton('refcoursestatic', 'refcourse', 'subcourse');
                $mform->addElement('hidden', 'refcourse', $this->current->refcourse);
                $mform->setType('refcourse', PARAM_INT);
            }
        } else {
            $catlist = array();
            $catparents = array();
            make_categories_list($catlist, $catparents);
            $options = array();
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
            $mform->addElement('selectgroups', 'refcourse', get_string('refcourselabel', 'subcourse'), $options);
            $mform->addHelpButton('refcourse', 'refcourse', 'subcourse');
        }

        // Common module settings ----------------------------------------------
        $this->standard_coursemodule_elements();

        // Common action buttons
        $this->add_action_buttons();
    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = array();

        if (empty($data['refcourse'])) {
            $errors['subcoursefieldset'] = ''; // The field not present, no need to set a message.
        }

        return $errors;
    }
}

