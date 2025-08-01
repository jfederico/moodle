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

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\recording;
use advanced_testcase;

/**
 * Recording action tests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 - present, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @coversDefaultClass \mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_action
 */
final class recording_action_test extends \advanced_testcase {
    /**
     * Test that deleting a recording sets its status to deleted.
     *
     * @covers \mod_bigbluebuttonbn\local\bigbluebuttonbn\recordings\recording_action::delete
     */
    public function test_delete_sets_status_deleted(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a dummy course and instance, as required by the module generator.
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $recordingdata = [
            'bigbluebuttonbnid' => $instance->id,
            'recordingid' => 'testrecid',
            'status' => 0, // Default status, adjust as needed for your schema.
            'imported' => false,
            'courseid' => $course->id,
            'groupid' => 0, // Add required field for persistent.
        ];
        $recordingid = $DB->insert_record('bigbluebuttonbn_recordings', $recordingdata);
        $recording = new recording($recordingid);

        // Call delete and verify status change.
        recording_action::delete($recording);
        $updated = $DB->get_record('bigbluebuttonbn_recordings', ['id' => $recordingid]);
        $this->assertEquals(recording::RECORDING_STATUS_DELETED, $updated->status);
    }
}
