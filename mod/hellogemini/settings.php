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
 * Global settings for the hellogemini module.
 * These settings appear in Site administration > Plugins > Activity modules > Hello Gemini.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) { // Ensure this is only included in the admin settings tree.

    // Example of a setting:
    // $settings->add(new admin_setting_configtext(
    //     'hellogemini/defaultmessage', // Setting name (pluginname/settingname)
    //     get_string('defaultmessage', 'mod_hellogemini'), // Setting title
    //     get_string('defaultmessage_desc', 'mod_hellogemini'), // Setting description
    //     'Hello Universe', // Default value
    //     PARAM_TEXT // Type of parameter
    // ));
    //
    // You would need to add the corresponding strings to your lang file:
    // $string['defaultmessage'] = 'Default greeting message';
    // $string['defaultmessage_desc'] = 'Enter the default message to be displayed if not set at instance level.';

    // For now, no global settings are defined for this basic plugin.
    // A link to the settings page will still appear under Activity modules.
    // To make it more useful, you could add settings here.
}
