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

namespace mod_bigbluebuttonbn;

use Firebase\JWT\JWT;
use mod_bigbluebuttonbn\hook\resolve_connection;
use mod_bigbluebuttonbn\local\connection;
use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Tests for the broker that handles callbacks from the BigBlueButton server.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(broker::class)]
final class broker_test extends \advanced_testcase {
    use testcase_helper_trait;

    /**
     * Create an activity with a recording awaiting its "recording ready" callback.
     *
     * @return array [instance, recording]
     */
    protected function create_activity_with_awaiting_recording(): array {
        [, , $bbactivity] = $this->create_instance();
        $instance = instance::get_from_instanceid($bbactivity->id);
        $recording = new recording(0, (object) [
            'courseid' => $instance->get_course_id(),
            'bigbluebuttonbnid' => $instance->get_instance_id(),
            'groupid' => 0,
            'recordingid' => 'recording-ready-1',
            'status' => recording::RECORDING_STATUS_AWAITING,
        ]);
        $recording->create();
        return [$instance, $recording];
    }

    /**
     * Send a "recording ready" callback signed with a secret.
     *
     * @param instance $instance
     * @param string $secret
     */
    protected static function send_recording_ready(instance $instance, string $secret): void {
        $token = JWT::encode(['record_id' => 'recording-ready-1'], $secret, 'HS256');
        broker::process_recording_ready($instance, ['signed_parameters' => $token]);
    }

    /**
     * With the site server, the callback is verified with the site secret, as before.
     *
     * Runs in a separate process because the broker sends HTTP headers.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_recording_ready_site_secret(): void {
        $this->resetAfterTest();
        set_config('bigbluebuttonbn_shared_secret', 'site-secret');
        [$instance, $recording] = $this->create_activity_with_awaiting_recording();

        self::send_recording_ready($instance, 'wrong-secret');
        $recording->read();
        $this->assertEquals(recording::RECORDING_STATUS_AWAITING, $recording->get('status'));

        self::send_recording_ready($instance, 'site-secret');
        $recording->read();
        $this->assertEquals(recording::RECORDING_STATUS_NOTIFIED, $recording->get('status'));
    }

    /**
     * When the activity uses another server, the callback must be signed with that server's secret.
     *
     * Runs in a separate process because the broker sends HTTP headers.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function test_recording_ready_activity_secret(): void {
        $this->resetAfterTest();
        set_config('bigbluebuttonbn_shared_secret', 'site-secret');
        [$instance, $recording] = $this->create_activity_with_awaiting_recording();

        $courseid = $instance->get_course_id();
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use ($courseid): void {
                if ($hook->courseid == $courseid) {
                    $hook->set_connection(new connection('https://other.example.com/bigbluebutton/', 'other-secret', 'SHA256'));
                }
            }
        );

        // The site secret no longer applies to this activity.
        self::send_recording_ready($instance, 'site-secret');
        $recording->read();
        $this->assertEquals(recording::RECORDING_STATUS_AWAITING, $recording->get('status'));

        self::send_recording_ready($instance, 'other-secret');
        $recording->read();
        $this->assertEquals(recording::RECORDING_STATUS_NOTIFIED, $recording->get('status'));
    }
}
