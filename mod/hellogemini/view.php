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
 * View page for the hellogemini module.
 * This page displays the content of a hellogemini activity instance.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.

// Get the course module record, which links the module instance to the course.
if (!$cm = get_coursemodule_from_id('hellogemini', $id)) {
    print_error('invalidcoursemodule');
}

// Get the course record.
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    print_error('coursemisconf');
}

// Get the hellogemini instance record from the 'hellogemini' table.
if (!$hellogemini = $DB->get_record('hellogemini', ['id' => $cm->instance])) {
    print_error('invalidid', 'hellogemini');
}

// Require user to be logged in and enrolled in the course (or have capability to view).
require_login($course, true, $cm);

// Get the context for this module instance.
$context = context_module::instance($cm->id);

// Check if the user has the capability to view this hellogemini instance.
require_capability('mod/hellogemini:view', $context);

// Add an entry to the Moodle log.
add_to_log($course->id, 'hellogemini', 'view', "view.php?id={$cm->id}", $hellogemini->id, $cm->id);

// Set up the page.
$PAGE->set_url('/mod/hellogemini/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($hellogemini->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output the page header.
echo $OUTPUT->header();

// Display the introduction/description if it exists and is not empty.
if (trim(strip_tags($hellogemini->intro))) {
    echo $OUTPUT->box_start('generalbox mod_introbox');
    echo format_module_intro('hellogemini', $hellogemini, $cm->id);
    echo $OUTPUT->box_end();
}

// Display the "Hello Gemini" message.
echo $OUTPUT->box_start('generalbox hellogeminibox'); // You can add a custom CSS class here.
echo html_writer::tag('p', get_string('displaymessage', 'mod_hellogemini'), ['class' => 'hellogemini-message']);
echo $OUTPUT->box_end();

// Trigger the course module viewed event.
// This is important for activity completion and reports.
$event = \mod_hellogemini\event\course_module_viewed::create([
    'objectid' => $hellogemini->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('hellogemini', $hellogemini);
$event->trigger();

// Output the page footer.
echo $OUTPUT->footer();
