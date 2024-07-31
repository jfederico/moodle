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
 * Show BBB reports available to teachers to view all grades.
 *
 * @package   mod_bigbluebuttonbn
 * @category  grade
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
**/

require_once("../../config.php");

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('bigbluebuttonbn', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', array('id' => $cm->instance), '*', MUST_EXIST);

$contextmodule = context_module::instance($cm->id);

$url = new moodle_url('/mod/bigbluebuttonbn/report.php');

$PAGE->set_url($url);

require_login($course, false, $cm);
require_capability('moodle/grade:viewall', $contextmodule);

$PAGE->set_pagelayout('report');
$PAGE->set_context($contextmodule);
$PAGE->set_heading(format_string($course->fullname));

// Output the header
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gradesreport', 'bigbluebuttonbn'));


// Fetch grades for the specific instance
$sql = "SELECT u.id, u.id AS userid, g.grade AS rawgrade
        FROM {user} u
        JOIN {bigbluebuttonbn_grades} g ON u.id = g.userid
        WHERE g.bigbluebuttonbnid = :bigbluebuttonbnid";

$params = array('bigbluebuttonbnid' => $bigbluebuttonbn->id);
$grades = $DB->get_records_sql($sql, $params);

// Display the grades in a table
$table = new html_table();
$table->head = array(get_string('username'), get_string('grade', 'bigbluebuttonbn'));
$table->align = array('left', 'center');

foreach ($grades as $grade) {
    $user = $DB->get_record('user', array('id' => $grade->userid), 'fullname');
    $table->data[] = array($user->fullname, $grade->rawgrade);
}

echo html_writer::table($table);

// Output the footer
echo $OUTPUT->footer();