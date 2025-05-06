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

namespace mod_hellogemini\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The hellogemini course module viewed event.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialise the instance.
     */
    protected function init() {
        $this->data['objecttable'] = 'hellogemini';
        parent::init();
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        // This is a default description. You might want to make it more specific.
        return "The user with id '{$this->userid}' viewed the '{$this->objecttable}' activity with course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Get the legacy event name.
     *
     * This function is used for backwards compatibility with the old event system.
     *
     * @return string
     */
    public static function get_legacy_eventname() {
        // For \core\event\course_module_viewed, the legacy event name is 'course_module_viewed'.
        // If your module had a different legacy event name, you would specify it here.
        // Since this is a new module, directly mapping to the core event log is usually fine.
        return parent::get_legacy_eventname();
    }

    /**
     * Get the URL related to this event.
     *
     * @return \moodle_url|null
     */
    public function get_url() {
        return new \moodle_url('/mod/hellogemini/view.php', ['id' => $this->contextinstanceid]);
    }
}
