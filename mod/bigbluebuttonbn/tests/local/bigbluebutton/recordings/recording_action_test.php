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
    public function test_delete_recording(): void {
        global $DB;

        $this->resetAfterTest();

        // Create the course and BigBlueButton activity the recording belongs to.
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
            'course' => $course->id,
            'trackactivity' => [],
        ]);
        $user = $this->getDataGenerator()->create_user();

        // Insert a recording row with the required schema fields populated.
        $now = time();
        $recordingdata = [
            'bigbluebuttonbnid' => $instance->id,
            'recordingid' => 'testrecid',
            'courseid' => $course->id,
            'groupid' => 0,
            'headless' => 0,
            'imported' => false,
            'status' => recording::RECORDING_STATUS_PROCESSED,
            'importeddata' => null,
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $user->id,
        ];
        $recordingid = $DB->insert_record('bigbluebuttonbn_recordings', $recordingdata);
        $recording = new recording($recordingid);

        // Call delete and verify status change.
        recording_action::delete($recording);

        // Verify the recording status is set to deleted.
        $updated = $DB->get_record('bigbluebuttonbn_recordings', ['id' => $recordingid]);
        $this->assertEquals(recording::RECORDING_STATUS_DELETED, $updated->status);
    }

    /**
     * Test that deleting an imported recording removes it from the database entirely.
     *
     * Imported recordings act as symbolic links stored only in Moodle so their record must be purged on delete.
     *
     * @covers \mod_bigbluebuttonbn\local\bigbluebuttonbn\recordings\recording_action::delete
     */
    public function test_delete_imported_recording(): void {
        global $DB;

        $this->resetAfterTest();

        // Create the course and BigBlueButton activity the imported recording belongs to.
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
            'course' => $course->id,
            'trackactivity' => [],
        ]);
        $user = $this->getDataGenerator()->create_user();

        // Insert an imported recording row with the required schema fields populated.
        $now = time();
        $metadata = [
            'recordID' => 'imported-rec-001',
            'meetingID' => 'sample-meeting-001',
            'meetingName' => 'Sample Meeting',
            'published' => 'true',
            'state' => 'published',
            'startTime' => '1744396087329',
            'endTime' => '1744396105898',
            'playbacks' => [
                'presentation' => [
                    'type' => 'presentation',
                    'url' => 'https://example.org/playback/presentation/sample',
                    'length' => '0',
                ],
            ],
        ];
        $recordingdata = [
            'bigbluebuttonbnid' => $instance->id,
            'courseid' => $course->id,
            'groupid' => 0,
            'recordingid' => 'imported-rec-001',
            'headless' => 0,
            'imported' => true,
            'status' => recording::RECORDING_STATUS_PROCESSED,
            'importeddata' => json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => $user->id,
        ];
        $recordingid = $DB->insert_record('bigbluebuttonbn_recordings', $recordingdata);
        $recording = new recording($recordingid);

        // Call delete and verify full removal.
        recording_action::delete($recording);

        // Verify the imported recording is fully removed.
        $this->assertFalse(
            $DB->record_exists('bigbluebuttonbn_recordings', ['id' => $recordingid]),
            'Imported recordings should be fully removed once deleted.'
        );
    }
}
