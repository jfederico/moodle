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

namespace mod_bigbluebuttonbn\local\config;

use context;
use mod_bigbluebuttonbn\extension;
use mod_bigbluebuttonbn\local\extension\config_provider_addon;

/**
 * Central resolver for per-tenant configuration.
 *
 * The resolver discovers the config provider addons contributed by enabled bbbext
 * subplugins and asks each, in turn, for a configuration override that applies to the
 * tenant owning a given context. When no provider supplies a value the caller falls back
 * to the site-global configuration ({@see \mod_bigbluebuttonbn\local\config}). On a site
 * without any config provider addon (e.g. a standard, single-tenant Moodle) the resolver
 * is inert and the plugin behaves exactly as it did before multitenancy support was added.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class resolver {

    /** @var config_provider_addon[]|null Cached list of enabled config provider addons for this request. */
    protected static ?array $providers = null;

    /** @var int[] Stack of ambient tenant ids for background/batch operations (see run_for_tenant()). */
    protected static array $tenantscope = [];

    /**
     * Run a callback with an ambient tenant scope in force.
     *
     * While the callback runs, every context-less {@see resolve_setting()} call (including the
     * static configuration reads buried inside the proxy stack) resolves against $tenantid,
     * rather than the current user's tenant. This lets site-wide operations (scheduled tasks,
     * CLI) act on one tenant's BigBlueButton server at a time without threading a context
     * through every method.
     *
     * @param int $tenantid the tenant id to scope to.
     * @param callable $callback the operation to run within the scope.
     * @return mixed the callback's return value.
     */
    public static function run_for_tenant(int $tenantid, callable $callback) {
        self::$tenantscope[] = $tenantid;
        try {
            return $callback();
        } finally {
            array_pop(self::$tenantscope);
        }
    }

    /**
     * The tenant id of the innermost active ambient scope, or null when none is in force.
     *
     * @return int|null
     */
    public static function current_scope_tenant(): ?int {
        return empty(self::$tenantscope) ? null : end(self::$tenantscope);
    }

    /**
     * Whether any configuration provider is enabled.
     *
     * Call sites use this to avoid the (potentially expensive) work of deriving a context
     * or tenant when no provider could act on it.
     *
     * @return bool
     */
    public static function is_active(): bool {
        return !empty(self::get_providers());
    }

    /**
     * Resolve a per-tenant override for a configuration setting.
     *
     * @param string $setting the setting name (without the bigbluebuttonbn_ prefix).
     * @param context|null $context the context whose tenant should be used; null uses the current tenant.
     * @return string|null the override value, or null when no provider overrides it.
     */
    public static function resolve_setting(string $setting, ?context $context = null): ?string {
        $providers = self::get_providers();
        if (empty($providers)) {
            return null;
        }
        // An explicit ambient scope (set by a background/batch operation) wins over context resolution.
        $scoped = self::current_scope_tenant();
        $tenantid = $scoped !== null ? $scoped : tenancy::get_tenant_id_for_context($context);
        foreach ($providers as $provider) {
            $value = $provider->resolve($setting, $tenantid);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Resolve a per-tenant override for a configuration setting for an explicit tenant id.
     *
     * Used by site-wide operations (scheduled tasks, CLI) that iterate tenants directly
     * rather than resolving a tenant from a context.
     *
     * @param string $setting
     * @param int $tenantid
     * @return string|null
     */
    public static function resolve_setting_for_tenant(string $setting, int $tenantid): ?string {
        foreach (self::get_providers() as $provider) {
            $value = $provider->resolve($setting, $tenantid);
            if ($value !== null) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Every tenant id that the plugin should iterate over for site-wide operations.
     *
     * @return int[] tenant ids; empty when multitenancy is unavailable.
     */
    public static function get_tenant_ids(): array {
        $tenantids = tenancy::get_all_tenant_ids();
        foreach (self::get_providers() as $provider) {
            $tenantids = array_merge($tenantids, $provider->get_tenant_ids());
        }
        return array_values(array_unique(array_map('intval', $tenantids)));
    }

    /**
     * Discover the enabled config provider addons contributed by bbbext subplugins.
     *
     * @return config_provider_addon[]
     */
    protected static function get_providers(): array {
        if (self::$providers === null) {
            self::$providers = extension::config_provider_addon_instances();
        }
        return self::$providers;
    }

    /**
     * Reset the request-level provider cache. Mainly used by tests and after enabling/disabling plugins.
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$providers = null;
    }
}
