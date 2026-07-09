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

namespace mod_bigbluebuttonbn\local\extension;

/**
 * Extension addon that resolves BigBlueButton configuration for a tenant.
 *
 * A bbbext subplugin ships a configuration provider by adding a class
 * <code>\bbbext_{name}\bigbluebuttonbn\config_provider_addon</code> that extends this class.
 * This is the extension point Moodle Workplace uses to serve per-tenant BigBlueButton
 * configuration (server URL, shared secret, feature toggles, ...). When no addon returns
 * a value, the plugin falls back to the site-global configuration held in
 * {@see \mod_bigbluebuttonbn\local\config}.
 *
 * Providers are consulted, in the subplugin order defined on the manage extensions page,
 * by {@see \mod_bigbluebuttonbn\local\config\resolver}; the first non-null value wins.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
abstract class config_provider_addon {

    /**
     * Resolve a single configuration setting for a tenant.
     *
     * Implementations must return a non-null value only when they hold an explicit
     * override for the given tenant and setting. Returning null lets the resolver try the
     * next addon and, ultimately, the site-global configuration.
     *
     * @param string $setting the setting name (without the bigbluebuttonbn_ prefix).
     * @param int $tenantid the tenant id to resolve for; 0 means "no tenant / site level".
     * @return string|null the override value, or null when this addon has no override.
     */
    abstract public function resolve(string $setting, int $tenantid): ?string;

    /**
     * List every tenant id this addon can serve configuration for.
     *
     * This is used by site-wide operations (scheduled tasks, CLI) that must iterate across
     * each tenant's server credentials rather than assume a single global server. The site
     * level (tenant id 0) is always handled by the global configuration and is therefore not
     * required to appear in the returned list.
     *
     * @return int[] list of tenant ids served by this addon.
     */
    public function get_tenant_ids(): array {
        return [];
    }
}
