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
 * View page for mod_hellodeepcode plugin.
 *
 * @package    mod_hellodeepcode
 * @copyright 2024 Deepcode AI (contact@deepcode.ai)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/hellodeepcode/lib.php');

// Get the course module
$cm = get_course_module($id);
if (!$cm) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $CFG->current_course->id, 
        get_string('invalidmodule', 'error'), null, NOTICE_ERROR);
}

// Check capabilities
if (!has_capability('mod/hellodeepcode:view', context_module::instance($cm->id))) {
    throw new moodle_exception(get_string('nopermission'));
}

// Output the content
echo $CFG->header;
echo '<div class="hello-deepcode">';
echo get_string('hello_deepcode', 'mod_hellodeepcode');
echo '</div>';
echo $CFG->footer;
