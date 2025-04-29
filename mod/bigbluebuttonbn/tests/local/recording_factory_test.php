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

use advanced_testcase;
use mod_bigbluebuttonbn\recording as core_recording;

/**
 * Tests for BigBlueButton
 *
 * @package   mod_bigbluebuttonbn
 * @category  test
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class recording_factory_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        // Reset Moodle state between tests.
        $this->resetAfterTest(true);
    }

    public function test_returns_core_recording_when_no_override_found(): void {
        // Simulate no subplugins.
        $this->mock_plugin_list([]);
        $this->mock_sorted_plugins([]);

        $resolved = recording_factory::resolve();
        $this->assertSame(core_recording::class, $resolved);
    }

    public function test_returns_override_when_available(): void {
        $this->mock_plugin_list([
            'testplugin' => '/some/fake/path',
        ]);

        $this->mock_sorted_plugins([
            0 => 'testplugin',
        ]);

        // Register a fake override class for this test.
        eval('
            namespace bbbext_testplugin;
            class recording_override extends \mod_bigbluebuttonbn\recording {}
        ');

        $resolved = recording_factory::resolve();
        $this->assertSame('bbbext_testplugin\\recording_override', $resolved);
    }

    private function mock_plugin_list(array $plugins): void {
        global $CFG;

        // Override core_component using monkey patching.
        // Since core_component is static and final, we can't mock it the usual way.
        // Instead, we override the autoloader during test setup.
        require_once($CFG->dirroot . '/lib/componentlib.class.php');

        // This simulates get_plugin_list() returning your test subplugins.
        $GLOBALS['mocked_plugins'] = $plugins;

        // Override method in extension class to use the global.
        extension::override_get_plugin_list_callback(function () use ($plugins) {
            return $GLOBALS['mocked_plugins'];
        });
    }

    private function mock_sorted_plugins(array $ordered): void {
        extension::override_get_sorted_plugins_list_callback(function () use ($ordered) {
            return $ordered;
        });
    }
}

