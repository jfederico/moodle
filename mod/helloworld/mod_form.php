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
 * Form definition for adding a new Hello World instance.
 *
 * @package    mod_helloworld
 * @copyright 2024 Your Name (your.email@example.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/helloworld/lib.php');

class mod_helloworld_mod_form extends moodleform {
    public function __construct($courseid = 0, $cmid = null) {
        parent::__construct();
        $this->courseid = $courseid;
        $this->cmid = $cmid;
    }

    public function get_data() {
        return $this->data;
    }

    public function definition() {
        $form = $this->_form;

        // Add form elements here
        $form->addElement('text', 'name', get_string('modform_instance_name', 'mod_helloworld'), [
            'required' => true,
            'maxlength' => 255
        ]);

        $form->add_action_buttons();
    }
}
