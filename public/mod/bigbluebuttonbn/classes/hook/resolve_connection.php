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

use mod_bigbluebuttonbn\local\connection;

/**
 * Allows plugins to choose the BigBlueButton server used for an activity or a course.
 *
 * The hook starts with the site connection. A callback may replace it, for example to send the
 * meetings of some courses to a different server. It is not dispatched when the connection is
 * forced in config.php.
 *
 * Both ids are optional: some callers only know the course, and site-level checks know neither.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to choose the BigBlueButton server used for an activity or a course.')]
#[\core\attribute\tags('mod_bigbluebuttonbn')]
final class resolve_connection {
    /**
     * Create the hook.
     *
     * @param int|null $instanceid id of the BigBlueButton activity, when known
     * @param int|null $courseid id of the course, when known
     * @param connection $connection the connection to use, initially the site connection
     */
    public function __construct(
        /** @var int|null Id of the BigBlueButton activity, when known */
        public readonly ?int $instanceid,
        /** @var int|null Id of the course, when known */
        public readonly ?int $courseid,
        /** @var connection The connection to use */
        private connection $connection,
    ) {
    }

    /**
     * Get the connection that will be used.
     *
     * @return connection
     */
    public function get_connection(): connection {
        return $this->connection;
    }

    /**
     * Replace the connection that will be used.
     *
     * @param connection $connection
     */
    public function set_connection(connection $connection): void {
        $this->connection = $connection;
    }
}
