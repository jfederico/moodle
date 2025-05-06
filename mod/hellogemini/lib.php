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
 * Library of functions and constants for the hellogemini module.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a new instance of the hellogemini module.
 * This function is called when a new instance of the module is created.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_hellogemini_mod_form $mform The form instance (optional).
 * @return int The ID of the newly created instance, or false on failure.
 * @throws moodle_exception If an error occurs.
 */
function hellogemini_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // The intro editor includes a format field.
    if (isset($data->introeditor)) {
        $data->introformat = $data->introeditor['format'];
        $data->intro = $data->introeditor['text'];
    } else {
        // Fallback if introeditor is not set (e.g. during upgrade or direct DB manipulation).
        if (!isset($data->introformat)) {
            $data->introformat = FORMAT_HTML;
        }
    }


    $data->id = $DB->insert_record('hellogemini', $data);

    // Set dates for the module instance.
    \core_course_activity_dates::set_dates_for_module_instance($data->course, $data->id, $data);

    return (int)$data->id;
}

/**
 * Updates an existing instance of the hellogemini module.
 * This function is called when an existing instance of the module is updated.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_hellogemini_mod_form $mform The form instance (optional).
 * @return bool True on success, false on failure.
 * @throws moodle_exception If an error occurs.
 */
function hellogemini_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance; // The instance ID is passed in $data->instance.

    // The intro editor includes a format field.
    if (isset($data->introeditor)) {
        $data->introformat = $data->introeditor['format'];
        $data->intro = $data->introeditor['text'];
    } else {
        // Fallback if introeditor is not set.
        if (!isset($data->introformat)) {
            $data->introformat = FORMAT_HTML;
        }
    }

    $result = $DB->update_record('hellogemini', $data);

    // Update dates for the module instance.
    \core_course_activity_dates::set_dates_for_module_instance($data->course, $data->id, $data);

    return $result;
}

/**
 * Deletes an instance of the hellogemini module.
 * This function is called when an instance of the module is deleted.
 *
 * @param int $id The ID of the instance to delete.
 * @return bool True on success, false on failure.
 */
function hellogemini_delete_instance(int $id): bool {
    global $DB;

    if (!$hellogemini = $DB->get_record('hellogemini', ['id' => $id])) {
        return false; // Instance not found.
    }

    // Perform any module-specific cleanup here (e.g., delete related files or records).

    $DB->delete_records('hellogemini', ['id' => $hellogemini->id]);

    return true;
}

/**
 * Returns a list of features that the hellogemini module supports.
 *
 * @param string $feature The feature to check (e.g., FEATURE_GROUPS).
 * @return mixed True if the feature is supported, null or false otherwise.
 */
function hellogemini_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_MOD_INTRO: // Does this module support an introduction field?
            return true;
        case FEATURE_SHOW_DESCRIPTION: // Show the description/intro on the course page?
            return true;
        case FEATURE_BACKUP_MOODLE2: // Does this module support Moodle 2 backup/restore?
            return true;
        case FEATURE_NO_VIEW_LINK: // Does this module have a view.php page? (false means yes)
            return false;
        case FEATURE_IDNUMBER: // Does this module support an ID number?
            return true;
        case FEATURE_GROUPS: // Does this module support groups?
            return false; // Not implementing group support for this basic plugin.
        case FEATURE_GROUPINGS: // Does this module support groupings?
            return false; // Not implementing grouping support.
        case FEATURE_MOD_ARCHETYPE: // What is the archetype of this module?
            return MOD_ARCHETYPE_RESOURCE; // Or MOD_ARCHETYPE_ACTIVITY if more interactive.
        case FEATURE_MOD_PURPOSE: // What is the purpose of this module?
            return MOD_PURPOSE_CONTENT;
        case FEATURE_COMPLETION_HAS_RULES: // Does this module have completion rules?
            return true;
        case FEATURE_GRADE_HAS_GRADE: // Does this module have a grade?
            return false; // No grading for this simple module.
        case FEATURE_GRADE_OUTCOMES: // Does this module use outcomes?
            return false; // No outcomes.
        default:
            return null; // Unknown feature.
    }
}

/**
 * Given a course_module object, this function returns any symbols that are substitutions.
 * This is used for displaying module information on the course page.
 *
 * @param cm_info $cm The course module object.
 * @return array|null An array of {@link \core\output\named_templatable} or null if not supported.
 */
function hellogemini_get_coursemodule_info(cm_info $cm): ?array {
    global $DB;

    if (!$moduleinstance = $DB->get_record('hellogemini', ['id' => $cm->instance], '*', IGNORE_MISSING)) {
        return null;
    }

    $info = new \stdClass();
    $info->name = $moduleinstance->name;
    // You can add other information here if needed for the course page.
    // For example, if you had a custom field in your module's table:
    // $info->customdata = $moduleinstance->customfield;

    $result = new \core\output\modinfo\course_module_info($info, $cm);
    return [$result];
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * It should delete any user-specific data from the module instance.
 *
 * @param stdClass $data The data submitted from the reset course form.
 * @return array Status array indicating success or failure for different components.
 */
function hellogemini_reset_userdata(stdClass $data): array {
    // This module does not store user-specific data beyond logs, which are handled by core.
    // If it did (e.g., user attempts, user preferences specific to this module instance),
    // you would add code here to delete that data.
    // Example: $DB->delete_records('hellogemini_userdata', ['hellogeminiid' => $data->instance]);
    return [];
}

/**
 * Add course module and instance specific information to the navigation block.
 *
 * @param navigation_node $node The node to extend.
 * @param stdClass $course The course record.
 * @param stdClass $module The module record.
 * @param cm_info $cm The course module record.
 * @param stdClass $context The context.
 * @return void
 */
function hellogemini_extend_navigation(navigation_node $node, stdclass $course, stdclass $module, cm_info $cm, stdclass $context): void {
    // This function can be used to add items to the navigation block.
    // For example, links to specific pages within the module.
    // For this simple plugin, we don't need to add anything here.
}

/**
 * Add module instance specific information to the settings block.
 *
 * @param settings_navigation $settingsnav The settings navigation object.
 * @param navigation_node $node The node to extend.
 * @param stdClass $course The course record.
 * @param stdClass $module The module record.
 * @param cm_info $cm The course module record.
 * @param stdClass $context The context.
 * @return void
 */
function hellogemini_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node, stdclass $course,
                                                stdclass $module, cm_info $cm, stdclass $context): void {
    // This function can be used to add items to the settings block for the module instance.
    // For this simple plugin, we don't need to add anything here.
}
