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

namespace bbbext_core_broker_analytics_callback\bigbluebuttonbn;

use mod_bigbluebuttonbn\broker;
use mod_bigbluebuttonbn\instance;


/**
 * A ....
 * When meeting_events callback is implemented by BigBlueButton, Moodle receives a POST request
 * which is processed in the function using super globals.
 *
 * @package   bbbext_core_broker_analytics_callback
 * @copyright 2024 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class bbb_broker_addons extends \mod_bigbluebuttonbn\local\extension\bbb_broker_addons {

    public function __construct(?instance $instance = null, ?string $action = null) {
        error_log("bbb_broker_addons::__construct");
        parent::__construct($instance, $action);
    }

    public function process_action($params): int {
        broker::process_meeting_events($this->instance);
        return true;
    }
}
