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
 * Form definition for the hellogemini module.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Form class for the hellogemini module.
 * This class defines the form used to create or update instances of the hellogemini module.
 */
class mod_hellogemini_mod_form extends moodleform_mod {

    /**
     * Defines the form structure.
     * This method is called by the parent moodleform_mod class to build the form.
     */
    protected function definition() {
        $mform = $this->_form; // Get the form object.

        // Adding the "General" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field (instance name).
        $mform->addElement('text', 'name', get_string('instancename', 'mod_hellogemini'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client'); // Name is required.
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'instancename', 'mod_hellogemini');

        // Adding the standard "intro" editor field (description).
        $this->add_intro_editor(); // This method is provided by moodleform_mod.

        // Add standard Moodle module elements (common settings, activity completion, etc.).
        $this->standard_coursemodule_elements();

        // Add standard action buttons (Save and display, Save and return to course, Cancel).
        $this->add_action_buttons();
    }

    /**
     * Custom validation for the form data.
     * This method can be overridden to add module-specific validation rules.
     *
     * @param array $data The data submitted from the form.
     * @param array $files The files submitted from the form.
     * @return array An array of error messages, or an empty array if validation passed.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Add any specific validation rules here if needed.
        // For example:
        // if (empty($data['customfield'])) {
        //     $errors['customfield'] = get_string('customfielderrornotempty', 'mod_hellogemini');
        // }
        return $errors;
    }
}
