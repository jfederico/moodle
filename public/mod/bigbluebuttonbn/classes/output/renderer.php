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

namespace mod_bigbluebuttonbn\output;

use core\notification;
use core\output\inplace_editable;
use html_table;
use html_writer;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\groups as groups_helper;
use plugin_renderer_base;

/**
 * Renderer for the mod_bigbluebuttonbn plugin.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the index table.
     *
     * @param  index $index
     * @return string
     */
    protected function render_index(index $index): string {
        $this->page->requires->js_call_amd('mod_bigbluebuttonbn/index', 'init');

        return html_writer::table($index->get_table($this));
    }

    /**
     * Render the groups selector.
     *
     * @param instance $instance
     * @return string
     */
    public function render_groups_selector(instance $instance): string { // Backwards compatible wrapper.
        return groups_helper::render_selector($instance);
    }

    /**
     * Render inplace editable
     *
     * @param inplace_editable $e
     * @return bool|string
     */
    public function render_inplace_editable(inplace_editable $e) {
        return $this->render_from_template('core/inplace_editable', $e->export_for_template($this));
    }
}
