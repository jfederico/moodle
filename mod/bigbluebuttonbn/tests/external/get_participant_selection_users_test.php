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

use core_external\external_api;

/**
 * Tests for the participant selection users external service.
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2026 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers \mod_bigbluebuttonbn\external\get_participant_selection_users
 */
final class get_participant_selection_users_test extends \core_external\tests\externallib_testcase {
    /**
     * Helper.
     *
     * @param mixed ...$params
     * @return array
     */
    protected function get_participant_selection_users(...$params): array {
        $users = get_participant_selection_users::execute(...$params);

        return external_api::clean_returnvalue(get_participant_selection_users::execute_returns(), $users);
    }

    /**
     * Test execute API call with a user that can add an activity.
     */
    public function test_execute_with_valid_login(): void {
        $this->resetAfterTest();
        $numstudents = 6;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        for ($i = 0; $i < $numstudents; $i++) {
            $generator->create_and_enrol($course, 'student');
        }
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $response = $this->get_participant_selection_users($course->id);

        $this->assertArrayHasKey('users', $response);
        $this->assertCount($numstudents + 1, $response['users']);
    }

    /**
     * Test execute API call without permission to add an activity.
     */
    public function test_execute_without_addinstance_capability(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course, 'student');
        $this->setUser($user);

        $this->expectException(\core_external\restricted_context_exception::class);
        $this->get_participant_selection_users($course->id);
    }
}
