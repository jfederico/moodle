<?php
// This file displays the Hello Deepcode activity to students

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/hellodeepcode/lib.php');

global $CFG, $DB;

if (!has_capability('mod/hellodeepcode:view', $context)) {
    throw new moodle_exception(get_string('nopermission'));
}

// Output the greeting
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo '<h1>' . get_string('helloworld', 'hellodeepcode') . '</h1>';
if (!empty($instance_name)) {
    echo '<p>Instance Name: ' . $instance_name . '</p>';
}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
