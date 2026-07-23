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

namespace mod_bigbluebuttonbn\plugininfo;

use mod_bigbluebuttonbn\test\subplugins_test_helper_trait;

/**
 * Tests for bbbext plugin lifecycle callbacks.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_bigbluebuttonbn\plugininfo\bbbext
 */
final class bbbext_test extends \advanced_testcase {
    use subplugins_test_helper_trait;

    /** @var string Fake callback message used by the guarded fixture. */
    private const ENABLE_BLOCKED_MESSAGE = 'Guarded test extension cannot be enabled right now.';

    /** @var string Fake callback warning used by the guarded fixture. */
    private const MANAGEMENT_WARNING_MESSAGE = 'Guarded test extension has a non-blocking warning.';

    /**
     * Sets up the fake subplugin fixtures before each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', true);
        $this->setup_fake_plugin('simple');
        $this->setup_fake_plugin('guarded');
        $this->resetDebugging();
    }

    /**
     * Uninstalls the fake subplugin fixtures after each test.
     *
     * @return void
     */
    protected function tearDown(): void {
        $this->uninstall_fake_plugin('guarded');
        $this->uninstall_fake_plugin('simple');
        parent::tearDown();
    }

    /**
     * Subplugins can block invalid enablement through plugininfo callbacks.
     *
     * @covers ::get_enable_blocking_message
     * @covers ::enable_plugin
     * @return void
     */
    public function test_enable_plugin_is_blocked_by_plugininfo_callback(): void {
        set_config('disabled', 1, 'bbbext_guarded');
        set_config('blockenable', 1, 'bbbext_guarded');

        $this->assertSame(
            self::ENABLE_BLOCKED_MESSAGE,
            bbbext::get_enable_blocking_message('guarded')
        );
        $this->assertFalse(bbbext::enable_plugin('guarded', 1));
        $this->assertSame('1', get_config('bbbext_guarded', 'disabled'));
    }

    /**
     * Subplugins can surface non-blocking warning messages for the manager UI.
     *
     * @covers ::get_management_warnings
     * @return void
     */
    public function test_get_management_warnings_uses_plugininfo_callback(): void {
        set_config('showwarning', 1, 'bbbext_guarded');

        $warnings = bbbext::get_management_warnings('guarded');

        $this->assertSame([
            self::MANAGEMENT_WARNING_MESSAGE,
        ], $warnings);
    }

    /**
     * Enabled subplugins are notified when another subplugin is disabled.
     *
     * @covers ::enable_plugin
     * @return void
     */
    public function test_disable_plugin_notifies_enabled_subplugins(): void {
        unset_config('disabled', 'bbbext_simple');
        $this->assertTrue(bbbext::enable_plugin('simple', 0));

        $this->assertSame('simple', get_config('bbbext_guarded', 'disabledplugin'));
    }
}
