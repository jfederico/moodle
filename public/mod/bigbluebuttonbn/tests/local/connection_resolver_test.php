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

namespace mod_bigbluebuttonbn\local;

use mod_bigbluebuttonbn\hook\resolve_connection;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\test\testcase_helper_trait;

/**
 * Tests for the connection and its resolver.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(connection::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(connection_resolver::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(resolve_connection::class)]
final class connection_resolver_test extends \advanced_testcase {
    use testcase_helper_trait;

    #[\Override]
    protected function tearDown(): void {
        global $CFG;
        unset($CFG->bigbluebuttonbn);
        parent::tearDown();
    }

    /**
     * Set the site connection settings.
     *
     * @param string $serverurl
     * @param string $secret
     * @param string $algorithm
     */
    protected function set_site_connection(
        string $serverurl = 'https://site.example.com/bigbluebutton/',
        string $secret = 'site-secret',
        string $algorithm = 'SHA256',
    ): void {
        set_config('bigbluebuttonbn_server_url', $serverurl);
        set_config('bigbluebuttonbn_shared_secret', $secret);
        set_config('bigbluebuttonbn_checksum_algorithm', $algorithm);
    }

    /**
     * The site connection follows the same precedence as config::get().
     */
    public function test_site_connection_precedence(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_site_connection();

        $site = connection_resolver::site();
        $this->assertSame('https://site.example.com/bigbluebutton/', $site->serverurl);
        $this->assertSame('site-secret', $site->sharedsecret);
        $this->assertSame('SHA256', $site->checksumalgorithm);
        $this->assertFalse($site->forced);

        $CFG->bigbluebuttonbn = ['server_url' => 'https://forced.example.com/bigbluebutton/'];
        $site = connection_resolver::site();
        $this->assertSame('https://forced.example.com/bigbluebutton/', $site->serverurl);
        $this->assertSame('site-secret', $site->sharedsecret);
        $this->assertTrue($site->forced);

        $CFG->bigbluebuttonbn = ['shared_secret' => 'forced-secret'];
        $site = connection_resolver::site();
        $this->assertSame('https://site.example.com/bigbluebutton/', $site->serverurl);
        $this->assertSame('forced-secret', $site->sharedsecret);
        $this->assertTrue($site->forced);

        // Other settings in the array do not force the connection.
        $CFG->bigbluebuttonbn = ['poll_interval' => 10];
        $this->assertFalse(connection_resolver::site()->forced);
    }

    /**
     * Whether a connection has both a server URL and a shared secret.
     *
     * @param string $serverurl
     * @param string $secret
     * @param bool $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('is_configured_provider')]
    public function test_is_configured(string $serverurl, string $secret, bool $expected): void {
        $this->assertSame($expected, (new connection($serverurl, $secret, 'SHA1'))->is_configured());
    }

    /**
     * Data provider for {@see test_is_configured()}.
     *
     * @return array
     */
    public static function is_configured_provider(): array {
        return [
            'both set' => ['https://bbb.example.com/bigbluebutton/', 'secret', true],
            'no url' => ['', 'secret', false],
            'no secret' => ['https://bbb.example.com/bigbluebutton/', '', false],
            'neither' => ['', '', false],
        ];
    }

    /**
     * The server id ignores formatting differences in the URL, and never depends on the secret.
     */
    public function test_get_id(): void {
        $reference = new connection('https://bbb.example.com/bigbluebutton/', 'one', 'SHA1');
        $variants = [
            'https://bbb.example.com/bigbluebutton',
            'https://BBB.example.com/bigbluebutton/',
            'https://bbb.example.com/bigbluebutton/api',
            'https://bbb.example.com/bigbluebutton/api/',
            ' https://bbb.example.com/bigbluebutton/ ',
        ];
        foreach ($variants as $variant) {
            $connection = new connection($variant, 'two', 'SHA256');
            $this->assertSame($reference->get_id(), $connection->get_id(), $variant);
            $this->assertTrue($reference->is_same_server($connection), $variant);
        }

        $other = new connection('https://other.example.com/bigbluebutton/', 'one', 'SHA1');
        $this->assertNotSame($reference->get_id(), $other->get_id());
        $this->assertFalse($reference->is_same_server($other));
    }

    /**
     * With no callback, every lookup returns the site connection.
     */
    public function test_without_callback(): void {
        $this->resetAfterTest();
        $this->set_site_connection();
        [, , $bbactivity] = $this->create_instance();
        $instance = instance::get_from_instanceid($bbactivity->id);

        $site = connection_resolver::site();
        $this->assertEquals($site, connection_resolver::for_instance($instance));
        $this->assertEquals($site, connection_resolver::for_instance((int) $bbactivity->id));
        $this->assertEquals($site, connection_resolver::for_course((int) $bbactivity->course));
    }

    /**
     * A callback can choose a different connection, and is given the ids it needs to decide.
     */
    public function test_callback_replaces_connection(): void {
        $this->resetAfterTest();
        $this->set_site_connection();
        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course();
        $courseb = $generator->create_course();
        [, , $activitya] = $this->create_instance($coursea);
        [, , $activityb] = $this->create_instance($courseb);

        $other = new connection('https://other.example.com/bigbluebutton/', 'other-secret', 'SHA512');
        $received = [];
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use ($courseb, $other, &$received): void {
                $received[] = [$hook->instanceid, $hook->courseid];
                if ($hook->courseid == $courseb->id) {
                    $hook->set_connection($other);
                }
            }
        );

        $site = connection_resolver::site();
        $this->assertEquals($site, connection_resolver::for_instance((int) $activitya->id));
        $this->assertEquals($other, connection_resolver::for_instance(instance::get_from_instanceid($activityb->id)));
        $this->assertEquals($other, connection_resolver::for_course((int) $courseb->id));

        $this->assertContains([(int) $activitya->id, (int) $coursea->id], $received);
        $this->assertContains([(int) $activityb->id, (int) $courseb->id], $received);
        $this->assertContains([null, (int) $courseb->id], $received);
    }

    /**
     * An activity that no longer exists is resolved without a course.
     */
    public function test_unknown_instance(): void {
        $this->resetAfterTest();
        $this->set_site_connection();

        $received = [];
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use (&$received): void {
                $received[] = [$hook->instanceid, $hook->courseid];
            }
        );

        $this->assertEquals(connection_resolver::site(), connection_resolver::for_instance(999999));
        $this->assertSame([[999999, null]], $received);
    }

    /**
     * A connection forced in config.php is never replaced, and the hook is not dispatched.
     */
    public function test_forced_connection_skips_hook(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->set_site_connection();
        [, , $bbactivity] = $this->create_instance();

        $called = false;
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use (&$called): void {
                $called = true;
                $hook->set_connection(new connection('https://other.example.com/bigbluebutton/', 'other', 'SHA1'));
            }
        );

        $CFG->bigbluebuttonbn = ['server_url' => 'https://forced.example.com/bigbluebutton/'];
        $connection = connection_resolver::for_instance((int) $bbactivity->id);

        $this->assertFalse($called);
        $this->assertSame('https://forced.example.com/bigbluebutton/', $connection->serverurl);
        $this->assertTrue($connection->forced);
    }

    /**
     * Results are cached for the request, but a change of the site settings is picked up.
     */
    public function test_cache_follows_site_settings(): void {
        $this->resetAfterTest();
        $this->set_site_connection();
        [, , $bbactivity] = $this->create_instance();

        $calls = 0;
        $this->redirectHook(
            resolve_connection::class,
            function (resolve_connection $hook) use (&$calls): void {
                $calls++;
            }
        );

        connection_resolver::for_instance((int) $bbactivity->id);
        connection_resolver::for_instance((int) $bbactivity->id);
        $this->assertSame(1, $calls);

        set_config('bigbluebuttonbn_shared_secret', 'changed-secret');
        $connection = connection_resolver::for_instance((int) $bbactivity->id);
        $this->assertSame(2, $calls);
        $this->assertSame('changed-secret', $connection->sharedsecret);
    }
}
