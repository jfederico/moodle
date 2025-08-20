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
/**
 * Group selector helper for BigBlueButtonBN.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace mod_bigbluebuttonbn\local\helpers;

use core\notification;
use mod_bigbluebuttonbn\instance;

class groups {
    /**
     * Returns group selector HTML (or empty string) and adds notifications when needed.
     *
     * @param instance $instance
     * @return string
     */
    public static function render_selector(instance $instance): string {
        $groupmode = \groups_get_activity_groupmode($instance->get_cm());
        if ($groupmode == NOGROUPS) {
            return '';
        }
        $allowedgroups = \groups_get_activity_allowed_groups($instance->get_cm());
        if (empty($allowedgroups)) {
            notification::add(\get_string('view_groups_nogroups_warning', 'bigbluebuttonbn'), notification::INFO);
            return '';
        }
        if (count($allowedgroups) > 1) {
            notification::add(\get_string('view_groups_selection_warning', 'bigbluebuttonbn'), notification::INFO);
        }
        $groupsmenu = \groups_print_activity_menu(
            $instance->get_cm(),
            $instance->get_view_url(),
            true
        );
        return $groupsmenu . '<br><br>';
    }
}
