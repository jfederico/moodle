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
 * View a BigBlueButton recordings.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Shamiso JAravaza (shamiso.jaravaza [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\output\view_page_recordings;
use mod_bigbluebuttonbn\plugin;

require(__DIR__ . '/../../config.php');
global $OUTPUT, $PAGE;

// Get the bbb instance from either the cmid (id), or the instanceid (bn).
$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $instance = instance::get_from_cmid($id);
} else {
    $bn = optional_param('bn', 0, PARAM_INT);
    if ($bn) {
        $instance = instance::get_from_instanceid($bn);
    }
}

if (!$instance) {
    throw new moodle_exception('view_error_url_missing_parameters', plugin::COMPONENT);
}

$cm = $instance->get_cm();
$course = $instance->get_course();
$bigbluebuttonbn = $instance->get_instance_data();

require_login($course, true, $cm);

// Require a working server.
bigbluebutton_proxy::require_working_server($instance);

// Print the page header.
$PAGE->set_url($instance->get_view_recordings_url());
$PAGE->set_title($cm->name);
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

// Output starts.
$renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');

try {
    $renderedinfo = $renderer->render(new view_page_recordings($instance));
} catch (server_not_available_exception $e) {
    bigbluebutton_proxy::handle_server_not_available($instance);
}

echo $OUTPUT->header();

// Valid credentials have not been setup, then we output a message to teachers and admin.
if (config::server_credentials_invalid()) {
    if (has_capability('moodle/site:config', context_system::instance())) {
        $settingslink = new moodle_url('/admin/settings.php', ['section' => 'modsettingbigbluebuttonbn']);
        echo $OUTPUT->notification(get_string('settings_credential_warning', 'bigbluebuttonbn',
            ['settingslink' => $settingslink->out()]), 'notifywarning');
    } else if (has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
        echo $OUTPUT->notification(get_string('settings_credential_warning_no_capability', 'bigbluebuttonbn'), 'notifywarning');
    }
}

echo $renderedinfo;

// Output finishes.
echo $OUTPUT->footer();
