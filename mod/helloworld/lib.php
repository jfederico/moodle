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
 * Core functionality for mod_helloworld plugin.
 *
 * @package    mod_helloworld
 * @copyright 2024 Your Name (your.email@example.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/helloworld/lib.php');

function mod_helloworld_add_instance($courseid, $userid = 0, $data) {
    global $DB;

    // Validate data here if needed
    $record = [
        'courseid' => $courseid,
        'userid' => $userid,
        'name' => $data['name'],
        'timecreated' => time()
    ];

    return $DB->insert_record('helloworld', $record);
}

function mod_helloworld_view($instanceid) {
    global $CFG, $DB;

    // Check if user has capability to view
    require_capability('mod/helloworld:view');

    // Load the instance data
    $instance = $DB->get_record('helloworld', ['id' => $instanceid]);

    // Output the greeting
    echo '<div class="hello-world">';
    echo get_string('helloworld_greeting', 'mod_helloworld');
    if (!empty($instance->name)) {
        echo ' - ' . $instance->name;
    }
    echo '</div>';
}

function mod_helloworld_delete_instance($id) {
    global $DB;

    return $DB->delete_record('helloworld', ['id' => $id]);
}
