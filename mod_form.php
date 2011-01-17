<?php //$Id

/**
 * Defines the main subcourse configuration form
 */

require_once ('moodleform_mod.php');

class mod_subcourse_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        $mform    =& $this->_form;

// General settings -------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('subcoursename', 'subcourse'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
    /// Adding the optional "intro" and "introformat" pair of fields
        $mform->addElement('htmleditor', 'intro', get_string('subcourseintro', 'subcourse'));
        $mform->setType('intro', PARAM_RAW);
        $mform->addRule('intro', get_string('required'), 'required', null, 'client');
        $mform->setHelpButton('intro', array('writing', 'richtext'), false, 'editorhelpbutton');

        $mform->addElement('format', 'introformat', get_string('format'));

// Subcourse information --------------------------------------------------------
        $mform->addElement('header', 'subcoursefieldset', get_string('refcourse', 'subcourse'));
        
    /// Referenced course selector
        $mycourses = subcourse_available_courses();
        $catlist = array();
        $catparents = array();
        make_categories_list($catlist, $catparents);
        $options = array();
        foreach ($mycourses as $mycourse) {
            if (empty($options[$catlist[$mycourse->category]])) {
                $options[$catlist[$mycourse->category]] = array();
            }
            $options[$catlist[$mycourse->category]][$mycourse->id] = $mycourse->fullname.' ('.$mycourse->shortname.')';
            if (empty($mycourse->visible)) {
                $options[$catlist[$mycourse->category]][$mycourse->id] .= ' '.get_string('hiddencourse', 'subcourse');
            }
        }
        unset($mycourse);
        $mform->addElement('selectgroups', 'refcourse', get_string('refcourselabel', 'subcourse'), $options);
        $mform->setHelpButton('refcourse', array('refcourse', get_string('refcourse', 'subcourse'), 'subcourse'));


//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }
}

?>
