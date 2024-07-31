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
 * This page is the entry page into the BigBlueButton UI. Displays information about the
 * BigBlueButton activity to students and teachers.
 *
 * @package   mod_bigbluebuttonbn
 * @category  grade
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

require_once("../../config.php");

$id = required_param('id', PARAM_INT); // Course module ID.
$userid = optional_param('userid', 0, PARAM_INT);

if (! $cm = get_coursemodule_from_id('bigbluebuttonbn', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}

if (! $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance))) {
    throw new \moodle_exception('invalidcoursemodule');
}

if (! $course = $DB->get_record('course', array('id' => $bigbluebuttonbn->course))) {
    throw new \moodle_exception('coursemisconf');
}

require_login($course, false, $cm);

$PAGE->set_url('/mod/bigbluebuttonbn/grade.php', array('id'=>$cm->id));

// Redirect depending on user permissions.
if (has_capability('moodle/grade:viewall', context_module::instance($cm->id))) {
    redirect('report.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}

