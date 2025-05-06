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
 * Callbacks and helper functions for mod_hellodeepcode plugin.
 *
 * @package    mod_hellodeepcode
 * @copyright 2024 Deepcode AI (contact@deepcode.ai)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');

/**
 * Callback function to add a new instance of the module.
 *
 * @param object $course The course object
 * @param array $module Array of module settings
 * @param int $sectionid Section ID where the module is added
 * @return bool True on success, false otherwise
 */
function mod_hellodeepcode_add_instance($course, $module, $sectionid) {
    global $DB;

    // Save the instance data to the database
    $record = array(
        'course_id' => $course->id,
        'name' => $module['name'],
        'timecreated' => time(),
        'createtime' => time()
    );

    return $DB->insert_record('hellodeepcode_instances', $record);
}

/**
 * Callback function to view the module.
 *
 * @param object $course The course object
 * @param object $module The module object
 * @param int $cmid Course module ID
 * @return bool True on success, false otherwise
 */
function mod_hellodeepcode_view($course, $module, $cmid) {
    global $DB;

    // Retrieve the instance data from the database
    if ($instance = $DB->get_record('hellodeepcode_instances', array('id' => $cmid))) {
        return true;
    }

    return false;
}
