<?php
// This file defines the form for creating a new Hello Deepcode instance

defined('MOODLE_INTERNAL') || die();

class mod_hellodeepcode_mod_form extends moodleform {
    public function definition() {
        $this->add_action_buttons();
        
        // Add custom fields here
        $this->addField('name', get_string('instance_name', 'hellodeepcode'), 
            null, null, array('required' => true));
    }
}
