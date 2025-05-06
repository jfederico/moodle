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
 * Index page for the hellogemini module.
 *
 * This page usually lists all instances of the hellogemini module in a course.
 * For simple modules, it often just redirects to the course view page.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

// Get the course record.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

// Require the user to be logged into the course.
require_course_login($course);

// Redirect to the course view page.
$url = new moodle_url('/course/view.php', ['id' => $id]);
redirect($url);
