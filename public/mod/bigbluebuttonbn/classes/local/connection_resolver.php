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

use cache;
use cache_store;
use mod_bigbluebuttonbn\hook\resolve_connection;
use mod_bigbluebuttonbn\instance;

/**
 * Decides which BigBlueButton server to use for an activity, a course or the site.
 *
 * Every piece of code that talks to a BigBlueButton server, or verifies a request coming from one,
 * should get its connection from here instead of reading the server settings directly.
 *
 * With no {@see resolve_connection} callback, the result is always the site connection, exactly as
 * {@see config::get()} returns it.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class connection_resolver {
    /**
     * The connection from the site configuration, with no plugin involved.
     *
     * @return connection
     */
    public static function site(): connection {
        return connection::from_site_config();
    }

    /**
     * The connection to use for a BigBlueButton activity.
     *
     * @param instance|int $instance the activity, or its bigbluebuttonbn id
     * @return connection
     */
    public static function for_instance(instance|int $instance): connection {
        global $DB;

        if ($instance instanceof instance) {
            return self::resolve($instance->get_instance_id(), $instance->get_course_id());
        }

        $courseid = $DB->get_field('bigbluebuttonbn', 'course', ['id' => $instance]);
        return self::resolve($instance, $courseid ? (int) $courseid : null);
    }

    /**
     * The connection to use in a course, when no particular activity is involved.
     *
     * @param int $courseid
     * @return connection
     */
    public static function for_course(int $courseid): connection {
        return self::resolve(null, $courseid);
    }

    /**
     * Let plugins choose the connection, starting from the site connection.
     *
     * Results are cached for the rest of the request. The cache key includes the site values, so a
     * change of the site settings within the same request is picked up.
     *
     * @param int|null $instanceid
     * @param int|null $courseid
     * @return connection
     */
    private static function resolve(?int $instanceid, ?int $courseid): connection {
        $site = self::site();
        if ($site->forced) {
            // Values forced in config.php always win, as they always have.
            return $site;
        }

        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'mod_bigbluebuttonbn', 'connections');
        $key = implode('_', [
            $instanceid ?? 0,
            $courseid ?? 0,
            sha1($site->serverurl . "\n" . $site->sharedsecret . "\n" . $site->checksumalgorithm),
        ]);
        $connection = $cache->get($key);
        if ($connection instanceof connection) {
            return $connection;
        }

        $hook = new resolve_connection($instanceid, $courseid, $site);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $connection = $hook->get_connection();

        $cache->set($key, $connection);
        return $connection;
    }
}
