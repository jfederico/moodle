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

namespace bbbext_guarded;

/**
 * Fake lifecycle callbacks for core plugininfo tests.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class plugininfo_callbacks {
    /** @var string Fake callback message used by core plugininfo tests. */
    private const ENABLE_BLOCKED_MESSAGE = 'Guarded test extension cannot be enabled right now.';

    /** @var string Fake callback warning used by core plugininfo tests. */
    private const MANAGEMENT_WARNING_MESSAGE = 'Guarded test extension has a non-blocking warning.';

    /**
     * Fake before_enable callback used by core plugininfo tests.
     *
     * @return string|null
     */
    public static function before_enable(): ?string {
        if (!empty(get_config('bbbext_guarded', 'blockenable'))) {
            return self::ENABLE_BLOCKED_MESSAGE;
        }

        return null;
    }

    /**
     * Fake management_warnings callback used by core plugininfo tests.
     *
     * @return string[]
     */
    public static function management_warnings(): array {
        if (empty(get_config('bbbext_guarded', 'showwarning'))) {
            return [];
        }

        return [self::MANAGEMENT_WARNING_MESSAGE];
    }

    /**
     * Fake post-disable callback used by core plugininfo tests.
     *
     * @param string $pluginname disabled subplugin shortname
     * @return void
     */
    public static function after_plugin_disabled(string $pluginname): void {
        set_config('disabledplugin', $pluginname, 'bbbext_guarded');
    }
}
