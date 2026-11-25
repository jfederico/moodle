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

namespace mod_bigbluebuttonbn\local\proxy;

use mod_bigbluebuttonbn\hook\resolve_connection;
use mod_bigbluebuttonbn\local\connection;
use mod_bigbluebuttonbn\local\connection_resolver;
use mod_bigbluebuttonbn\recording;
use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Tests that requests and cache entries follow the connection of the activity they belong to.
 *
 * None of these tests talk to a server, so they do not need the mock server.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(proxy_base::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(recording::class)]
final class connection_routing_test extends \advanced_testcase {
    use testcase_helper_trait;

    /** @var connection The connection a callback returns for the other course. */
    protected connection $other;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('bigbluebuttonbn_server_url', 'https://site.example.com/bigbluebutton/');
        set_config('bigbluebuttonbn_shared_secret', 'site-secret');
        set_config('bigbluebuttonbn_checksum_algorithm', 'SHA1');
        $this->other = new connection('https://other.example.com/bigbluebutton/', 'other-secret', 'SHA256');
    }

    /**
     * Send the activities of one course to the other server.
     *
     * @param int $courseid
     */
    protected function use_other_server_for_course(int $courseid): void {
        $other = $this->other;
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use ($courseid, $other): void {
                if ($hook->courseid == $courseid) {
                    $hook->set_connection($other);
                }
            }
        );
    }

    /**
     * Call a protected static method of proxy_base.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function call_protected(string $method, array $args): mixed {
        return (new \ReflectionMethod(proxy_base::class, $method))->invoke(null, ...$args);
    }

    /**
     * The checksum uses the algorithm and secret of the connection it is given.
     */
    public function test_get_checksum(): void {
        $this->assertSame(
            hash('SHA1', 'create' . 'meetingID=1' . 'site-secret'),
            proxy_base::get_checksum('create', 'meetingID=1')
        );
        $this->assertSame(
            hash('SHA256', 'create' . 'meetingID=1' . 'other-secret'),
            proxy_base::get_checksum('create', 'meetingID=1', $this->other)
        );
    }

    /**
     * Check that a request URL goes to a server and is signed with a secret.
     *
     * Installed extensions may add their own parameters, so the checksum is recomputed from the query.
     *
     * @param string $url the request URL
     * @param string $expectedbase the expected URL up to and including the action
     * @param string $action
     * @param string $algorithm
     * @param string $secret
     */
    protected function assert_signed_url(
        string $url,
        string $expectedbase,
        string $action,
        string $algorithm,
        string $secret,
    ): void {
        [$base, $query] = explode('?', $url, 2);
        $this->assertSame($expectedbase, $base);
        $this->assertMatchesRegularExpression('/^(.*)&checksum=([0-9a-f]+)$/', $query);
        preg_match('/^(.*)&checksum=([0-9a-f]+)$/', $query, $matches);
        $this->assertStringContainsString('meetingID=abc', $matches[1]);
        $this->assertSame(hash($algorithm, $action . $matches[1] . $secret), $matches[2]);
    }

    /**
     * A request for an activity is built for, and signed with, the activity's server.
     */
    public function test_action_url_uses_activity_connection(): void {
        $othercourse = $this->getDataGenerator()->create_course();
        [, , $otheractivity] = $this->create_instance($othercourse);
        $this->use_other_server_for_course((int) $othercourse->id);

        $url = self::call_protected('action_url', ['getMeetingInfo', ['meetingID' => 'abc'], [], (int) $otheractivity->id]);
        $this->assert_signed_url(
            $url,
            'https://other.example.com/bigbluebutton/api/getMeetingInfo',
            'getMeetingInfo',
            'SHA256',
            'other-secret'
        );
    }

    /**
     * Requests for activities on the site server, or with the site connection given explicitly, use the site secret.
     */
    public function test_action_url_uses_site_connection(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped('The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant is not defined.');
        }
        $generator = $this->getDataGenerator();
        $sitecourse = $generator->create_course();
        $othercourse = $generator->create_course();
        [, , $siteactivity] = $this->create_instance($sitecourse);
        [, , $otheractivity] = $this->create_instance($othercourse);
        $this->use_other_server_for_course((int) $othercourse->id);
        $mockbase = self::call_protected('sanitized_url', []) . 'getMeetingInfo';

        $url = self::call_protected('action_url', ['getMeetingInfo', ['meetingID' => 'abc'], [], (int) $siteactivity->id]);
        $this->assert_signed_url($url, $mockbase, 'getMeetingInfo', 'SHA1', 'site-secret');

        // An explicit connection wins over the activity id.
        $url = self::call_protected(
            'action_url',
            ['getMeetingInfo', ['meetingID' => 'abc'], [], (int) $otheractivity->id, connection_resolver::site()]
        );
        $this->assert_signed_url($url, $mockbase, 'getMeetingInfo', 'SHA1', 'site-secret');
    }

    /**
     * Requests to the site server still go to the mock server in unit tests.
     */
    public function test_sanitized_url_mock_server(): void {
        if (!defined('TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER')) {
            $this->markTestSkipped('The TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER constant is not defined.');
        }
        $mockurl = rtrim((new \moodle_url(TEST_MOD_BIGBLUEBUTTONBN_MOCK_SERVER))->out(false), '/');
        $this->assertStringStartsWith($mockurl, self::call_protected('sanitized_url', []));
        $this->assertSame(
            'https://other.example.com/bigbluebutton/api/',
            self::call_protected('sanitized_url', [$this->other])
        );
    }

    /**
     * Cache keys only change for servers other than the site server.
     */
    public function test_get_cache_key(): void {
        $this->assertSame('recording-1', proxy_base::get_cache_key('recording-1'));
        $this->assertSame('recording-1', proxy_base::get_cache_key('recording-1', connection_resolver::site()));

        // Same server with a different secret: same server, same cache entries.
        $sameserver = new connection('https://site.example.com/bigbluebutton', 'another-secret', 'SHA512');
        $this->assertSame('recording-1', proxy_base::get_cache_key('recording-1', $sameserver));

        $this->assertSame(
            $this->other->get_id() . '_recording-1',
            proxy_base::get_cache_key('recording-1', $this->other)
        );
    }

    /**
     * Recordings are grouped by the server of their activity, and a single server gives a single group.
     */
    public function test_recordings_grouped_by_connection(): void {
        $generator = $this->getDataGenerator();
        $sitecourse = $generator->create_course();
        $othercourse = $generator->create_course();
        [, , $siteactivity] = $this->create_instance($sitecourse);
        [, , $otheractivity] = $this->create_instance($othercourse);

        $records = [
            11 => (object) ['id' => 11, 'bigbluebuttonbnid' => $siteactivity->id, 'recordingid' => 'site-1'],
            12 => (object) ['id' => 12, 'bigbluebuttonbnid' => $otheractivity->id, 'recordingid' => 'other-1'],
            13 => (object) ['id' => 13, 'bigbluebuttonbnid' => $siteactivity->id, 'recordingid' => 'site-2'],
        ];
        $method = new \ReflectionMethod(recording::class, 'group_by_connection');

        $groups = $method->invoke(null, $records);
        $this->assertCount(1, $groups);
        $this->assertSame([11, 12, 13], array_keys($groups[0]['records']));

        // Connections are cached for the request, and callbacks never change within a real request.
        \cache::make_from_params(\cache_store::MODE_REQUEST, 'mod_bigbluebuttonbn', 'connections')->purge();
        $this->use_other_server_for_course((int) $othercourse->id);
        $groups = $method->invoke(null, $records);
        $this->assertCount(2, $groups);
        $this->assertEquals(connection_resolver::site(), $groups[0]['connection']);
        $this->assertSame([11, 13], array_keys($groups[0]['records']));
        $this->assertEquals($this->other, $groups[1]['connection']);
        $this->assertSame([12], array_keys($groups[1]['records']));
    }

    /**
     * Callers that give no server get the server of each recording's activity, or the site server.
     */
    public function test_group_by_recording_server(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $sitecourse = $generator->create_course();
        $othercourse = $generator->create_course();
        [, , $siteactivity] = $this->create_instance($sitecourse);
        [, , $otheractivity] = $this->create_instance($othercourse);
        $add = function (\stdClass $activity, string $recordingid, int $imported) use ($DB): void {
            $DB->insert_record('bigbluebuttonbn_recordings', [
                'courseid' => $activity->course,
                'bigbluebuttonbnid' => $activity->id,
                'recordingid' => $recordingid,
                'imported' => $imported,
                'status' => recording::RECORDING_STATUS_PROCESSED,
                'timecreated' => time(),
                'timemodified' => time(),
                'usermodified' => 0,
            ]);
        };
        $add($siteactivity, 'site-1', 0);
        $add($otheractivity, 'other-1', 0);
        // A copy of other-1 imported into the site activity: the recording stays on the other server.
        $add($siteactivity, 'other-1', 1);
        $method = new \ReflectionMethod(recording_proxy::class, 'group_by_recording_server');

        // With a single server, everything is one group on the site server.
        $groups = $method->invoke(null, ['site-1', 'other-1', 'unknown']);
        $this->assertCount(1, $groups);
        $this->assertEquals(connection_resolver::site(), $groups[0]['connection']);
        $this->assertSame(['site-1', 'other-1', 'unknown'], $groups[0]['ids']);

        \cache::make_from_params(\cache_store::MODE_REQUEST, 'mod_bigbluebuttonbn', 'connections')->purge();
        $this->use_other_server_for_course((int) $othercourse->id);
        $groups = $method->invoke(null, ['site-1', 'other-1', 'unknown', 'site-1']);
        $this->assertCount(2, $groups);
        $this->assertEquals(connection_resolver::site(), $groups[0]['connection']);
        $this->assertSame(['site-1', 'unknown'], $groups[0]['ids']);
        $this->assertEquals($this->other, $groups[1]['connection']);
        $this->assertSame(['other-1'], $groups[1]['ids']);
    }

    /**
     * A request for a recording without a server goes to the server of the recording's activity.
     */
    public function test_recording_request_without_connection(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        [, , $activity] = $this->create_instance($course);
        $DB->insert_record('bigbluebuttonbn_recordings', [
            'courseid' => $course->id,
            'bigbluebuttonbnid' => $activity->id,
            'recordingid' => 'rec-1',
            'imported' => 0,
            'status' => recording::RECORDING_STATUS_PROCESSED,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => 0,
        ]);
        $this->use_other_server_for_course((int) $course->id);

        $method = new \ReflectionMethod(recording_proxy::class, 'get_recording_connection');
        $this->assertEquals($this->other, $method->invoke(null, 'rec-1'));
        $this->assertEquals(connection_resolver::site(), $method->invoke(null, 'not-on-this-site'));
    }
}
