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
 * View page for Hello World activity.
 *
 * @package    mod_helloworld
 * @copyright 2024 Your Name (your.email@example.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/helloworld/lib.php');

function helloworld_view_page($course, $module) {
    global $OUTPUT;

    echo $OUTPUT->header();
    echo '<div class="helloworld-container">';
    mod_helloworld_view($module->id);
    echo '</div>';
    echo $OUTPUT->footer();
}
