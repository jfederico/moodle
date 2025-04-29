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

use core_component;
use mod_bigbluebuttonbn\extension;
use mod_bigbluebuttonbn\recording as core_recording;

/**
 * Class recording_factory
 *
 * @package    mod_bigbluebuttonbn
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
/**
 * Finds a recording handler supplied by the first bbbext_* sub-plugin.
 */
final class recording_factory {
    /**
     * Return the fully-qualified class name to use.
     *
     * @return string
     */
    public static function resolve(): string {
        $names = core_component::get_plugin_list(extension::BBB_EXTENSION_PLUGIN_NAME);
        $sortednames = extension::get_sorted_plugins_list($names);
        // Iterate installed sub-plugins.
        foreach ($sortednames as $name) {
            // Skip disabled plugins.
            $isdisabled = get_config(extension::BBB_EXTENSION_PLUGIN_NAME . '_' . $name, 'disabled');
            if ($isdisabled) {
                continue;
            }
            // Look for classes\recording.php or classes\recording_override.php.
            foreach (['recording_override', 'recording'] as $candidate) {
                $class = extension::BBB_EXTENSION_PLUGIN_NAME . '_' . $name . '\\' . $candidate;
                if (class_exists($class) && is_subclass_of($class, core_recording::class)) {
                    return $class;
                }
            }
        }
        // Fall back to the stock handler.
        return core_recording::class;
    }
}