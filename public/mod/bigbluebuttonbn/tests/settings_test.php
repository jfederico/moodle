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

use admin_category;
use admin_root;
use admin_setting_configmultiselect;
use admin_settingpage;
use ReflectionClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the BigBlueButton settings class.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_bigbluebuttonbn\settings
 */
final class settings_test extends \advanced_testcase {
    /**
     * Test that recording safe formats list uses configurable available formats.
     *
     * @covers ::add_record_settings
     */
    public function test_add_record_settings_uses_configurable_available_formats(): void {
        global $CFG;

        $this->resetAfterTest();

        $hadavailableformats = property_exists($CFG, 'bigbluebuttonbn_recording_safe_formats_options');
        $oldavailableformats = $hadavailableformats ? $CFG->bigbluebuttonbn_recording_safe_formats_options : null;
        $CFG->bigbluebuttonbn_recording_safe_formats_options = ' video , settings , new_type ';

        try {
            $admin = new admin_root(true);
            $admin->add('root', new admin_category('modbigbluebuttonbnfolder', 'BBB'));
            $settingshelper = $this->make_settings_helper($admin);
            $this->invoke_add_record_settings($settingshelper);

            $recordingsettings = $admin->locate('mod_bigbluebuttonbn_recording');
            $this->assertInstanceOf(admin_settingpage::class, $recordingsettings);
            $safeformatsetting = $this->find_setting($recordingsettings, 'bigbluebuttonbn_recording_safe_formats');

            $this->assertInstanceOf(admin_setting_configmultiselect::class, $safeformatsetting);
            $this->assertSame(['video', 'settings', 'new_type'], array_keys($safeformatsetting->choices));
            $this->assertSame('New Type', $safeformatsetting->choices['new_type']);
            $this->assertSame(['video'], $safeformatsetting->defaultsetting);
        } finally {
            if ($hadavailableformats) {
                $CFG->bigbluebuttonbn_recording_safe_formats_options = $oldavailableformats;
            } else {
                unset($CFG->bigbluebuttonbn_recording_safe_formats_options);
            }
        }
    }

    /**
     * Creates a settings helper object without running constructor side-effects.
     *
     * @param admin_root $admin
     * @return settings
     */
    private function make_settings_helper(admin_root $admin): settings {
        $reflection = new ReflectionClass(settings::class);
        $settingshelper = $reflection->newInstanceWithoutConstructor();
        $adminproperty = $reflection->getProperty('admin');
        $adminproperty->setAccessible(true);
        $adminproperty->setValue($settingshelper, $admin);
        $moduleenabledproperty = $reflection->getProperty('moduleenabled');
        $moduleenabledproperty->setAccessible(true);
        $moduleenabledproperty->setValue($settingshelper, true);

        return $settingshelper;
    }

    /**
     * Invoke the protected add_record_settings method.
     *
     * @param settings $settingshelper
     */
    private function invoke_add_record_settings(settings $settingshelper): void {
        $reflection = new ReflectionClass(settings::class);
        $method = $reflection->getMethod('add_record_settings');
        $method->setAccessible(true);
        $method->invoke($settingshelper);
    }

    /**
     * Find one setting in an admin settings page by setting name.
     *
     * @param admin_settingpage $settingspage
     * @param string $settingname
     * @return \admin_setting|null
     */
    private function find_setting(admin_settingpage $settingspage, string $settingname): ?\admin_setting {
        foreach ($settingspage->settings as $setting) {
            if ($setting->name === $settingname) {
                return $setting;
            }
        }
        return null;
    }
}
