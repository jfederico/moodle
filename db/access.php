<?php
// This file defines capabilities for the Hello Deepcode plugin

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    // Capability to view instances of this activity
    'mod/hellodeepcode:view' => array(
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'defstan' => false,
        'defrole' => ROLE_STUDENT,
    ),
    
    // Capability to add new instances
    'mod/hellodeepcode:addinstance' => array(
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'defstan' => false,
        'defrole' => ROLE_TEACHER,
    )
);
