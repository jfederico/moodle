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

namespace mod_bigbluebuttonbn\hook;

use settings_navigation;
use navigation_node;

/**
 * Class extend_settings_navigation_append
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2025 Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico (jesus [at] blindsidenetworks [dt] com)
 */
#[\core\attribute\label('Hook dispatched when we need to append some content into the setting navigation menu.')]
#[\core\attribute\tags('hook', 'settings_navigation', 'mod_bigbluebuttonbn')]
class extend_settings_navigation_append {
    /**
     * Constructor for the hook.
     *
     * @param settings_navigation $settingsnav The settings navigation object.
     * @param navigation_node $nodenav The node navigation object.
     * @return void
     */
    public function __construct(
        /** @var settings_navigation $settingsnav The settings navigation object. */
        public settings_navigation $settingsnav,
        /** @var navigation_node $nodenav The node navigation object to be extended. */
        public navigation_node $nodenav
    ) {
        $this->settingsnav = $settingsnav;
        $this->nodenav = $nodenav;
    }
}
