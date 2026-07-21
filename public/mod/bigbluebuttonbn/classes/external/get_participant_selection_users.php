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

namespace mod_bigbluebuttonbn\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use mod_bigbluebuttonbn\local\helpers\roles;

/**
 * External service to fetch users for the participant selector in the activity form.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class get_participant_selection_users extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'BigBlueButton activity id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get users available for the participant selector.
     *
     * @param int $courseid Course id
     * @param int $bigbluebuttonbnid BigBlueButton activity id
     * @return array
     * @throws restricted_context_exception
     */
    public static function execute(int $courseid, int $bigbluebuttonbnid = 0): array {
        global $DB;

        ['courseid' => $courseid, 'bigbluebuttonbnid' => $bigbluebuttonbnid] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'bigbluebuttonbnid' => $bigbluebuttonbnid,
        ]);

        $context = context_course::instance($courseid);
        self::validate_context($context);

        if (!has_capability('mod/bigbluebuttonbn:addinstance', $context)) {
            throw new restricted_context_exception();
        }

        $bigbluebuttonbn = null;
        if ($bigbluebuttonbnid) {
            $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', ['id' => $bigbluebuttonbnid], '*', MUST_EXIST);
            if ($bigbluebuttonbn->course != $courseid) {
                throw new \invalid_parameter_exception('The activity does not belong to the specified course.');
            }
        }

        return [
            'users' => array_values(roles::get_users_array($context, $bigbluebuttonbn)),
        ];
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id'),
                'name' => new external_value(PARAM_TEXT, 'User full name'),
            ])),
        ]);
    }
}
