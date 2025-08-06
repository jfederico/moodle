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

namespace bbbext_complex\hook;

use moodle_url;

/**
 * Class extend_settings_navigation_override example.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class extend_settings_navigation_override {
    /**
     * Overrides settings navigation.
     *
     * @param object $event The event object containing navigation context.
     * @return void
     */
    public static function override_settings_navigation(
        \mod_bigbluebuttonbn\hook\extend_settings_navigation_override $event
    ): void {
        // Get the settings navigation and node navigation.
        $nodenav = $event->nodenav;
        $nodenav->add(
            get_string('settings_navigation_override', 'bbbext_complex'),
            new moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => $nodenav->key]),
            \navigation_node::TYPE_SETTING,
            null,
            'bbbext_example_override'
        );
        $event->stop();
    }
}
