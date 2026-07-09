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
use core_course_category;

/**
 * Thin, defensive wrapper around the Moodle Workplace tenant API (tool_tenant).
 *
 * Every access to the proprietary Workplace API is guarded so the plugin keeps working
 * (as a single-tenant site) on installations where tool_tenant is not present. All
 * "no tenant / not available" situations resolve to tenant id 0, which the configuration
 * resolver treats as the site level.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class tenancy {

    /** @var int Tenant id used to mean "no tenant / site level". */
    public const NO_TENANT = 0;

    /**
     * Whether Moodle Workplace multitenancy support is available on this site.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return class_exists('\tool_tenant\tenancy');
    }

    /**
     * Get the tenant id for the currently logged-in user (or the active tenant of the request).
     *
     * @return int the tenant id, or {@see self::NO_TENANT} when multitenancy is unavailable.
     */
    public static function get_current_tenant_id(): int {
        if (!self::is_available()) {
            return self::NO_TENANT;
        }
        // \tool_tenant\tenancy::get_tenant_id() returns the tenant of the current user.
        return (int) \tool_tenant\tenancy::get_tenant_id();
    }

    /**
     * Resolve the tenant id that owns the given context.
     *
     * The tenant is derived from the course category the context lives in: a tenant
     * "owns" a course category (and everything beneath it). When the context is not
     * associated with any tenant category, or when multitenancy is unavailable, the
     * current user's tenant is used, falling back to the site level.
     *
     * @param context|null $context the context to resolve; null uses the current tenant.
     * @return int the resolved tenant id, or {@see self::NO_TENANT}.
     */
    public static function get_tenant_id_for_context(?context $context): int {
        if (!self::is_available()) {
            return self::NO_TENANT;
        }
        if ($context === null) {
            return self::get_current_tenant_id();
        }

        $categoryid = self::get_category_id_for_context($context);
        if ($categoryid === 0) {
            return self::get_current_tenant_id();
        }

        $tenantcategories = self::get_tenant_categories();
        if (empty($tenantcategories)) {
            return self::get_current_tenant_id();
        }

        // Walk from the context's own category up to the root, returning the nearest
        // ancestor category that belongs to a tenant.
        foreach (self::get_category_path_ids($categoryid) as $catid) {
            if (isset($tenantcategories[$catid])) {
                return $tenantcategories[$catid];
            }
        }
        return self::get_current_tenant_id();
    }

    /**
     * Get every tenant id defined on the site.
     *
     * Used by site-wide operations that must iterate per-tenant server credentials.
     *
     * @return int[] the list of tenant ids (empty when multitenancy is unavailable).
     */
    public static function get_all_tenant_ids(): array {
        if (!self::is_available()) {
            return [];
        }
        $tenantids = [];
        foreach (\tool_tenant\tenancy::get_tenants() as $tenant) {
            $tenantids[] = (int) $tenant->id;
        }
        return $tenantids;
    }

    /**
     * Map of tenant category id => tenant id.
     *
     * @return array<int, int>
     */
    protected static function get_tenant_categories(): array {
        $map = [];
        foreach (\tool_tenant\tenancy::get_tenants() as $tenant) {
            if (!empty($tenant->categoryid)) {
                $map[(int) $tenant->categoryid] = (int) $tenant->id;
            }
        }
        return $map;
    }

    /**
     * Find the course category id associated with a context.
     *
     * @param context $context
     * @return int the category id, or 0 when the context is not within a category.
     */
    protected static function get_category_id_for_context(context $context): int {
        // Course category contexts map directly.
        $catcontext = $context->get_parent_context();
        if ($context->contextlevel == CONTEXT_COURSECAT) {
            return (int) $context->instanceid;
        }
        // Module / block / course contexts: resolve through the owning course.
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return 0;
        }
        $course = get_course($coursecontext->instanceid);
        return (int) ($course->category ?? 0);
    }

    /**
     * Return the category id followed by its ancestor category ids, nearest first.
     *
     * @param int $categoryid
     * @return int[]
     */
    protected static function get_category_path_ids(int $categoryid): array {
        $category = core_course_category::get($categoryid, IGNORE_MISSING, true);
        if (!$category) {
            return [$categoryid];
        }
        // get_parents() returns ancestors root-first; reverse to nearest-first and prepend self.
        return array_merge([$categoryid], array_reverse($category->get_parents()));
    }
}
