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

/**
 * The details needed to talk to one BigBlueButton server.
 *
 * Use {@see connection_resolver} to obtain the connection for an activity or a course, rather than
 * reading the server settings directly.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class connection {
    /**
     * Create a connection.
     *
     * Values are kept exactly as given. Callers that build API requests are responsible for any
     * normalisation, as they are today.
     *
     * @param string $serverurl base URL of the BigBlueButton server
     * @param string $sharedsecret shared secret used to sign API calls and verify callbacks
     * @param string $checksumalgorithm hash algorithm used for API checksums, for example 'SHA1'
     * @param bool $forced true when the values are forced in config.php and must not be replaced
     */
    public function __construct(
        /** @var string Base URL of the BigBlueButton server */
        public readonly string $serverurl,
        /** @var string Shared secret used to sign API calls and verify callbacks */
        public readonly string $sharedsecret,
        /** @var string Hash algorithm used for API checksums */
        public readonly string $checksumalgorithm,
        /** @var bool True when the values are forced in config.php */
        public readonly bool $forced = false,
    ) {
    }

    /**
     * Build the connection from the site configuration.
     *
     * The precedence is the same as {@see config::get()}: a value in the $CFG->bigbluebuttonbn array
     * (config.php), then $CFG->bigbluebuttonbn_<setting>, then the default.
     *
     * @return self
     */
    public static function from_site_config(): self {
        global $CFG;

        $forced = isset($CFG->bigbluebuttonbn['server_url']) || isset($CFG->bigbluebuttonbn['shared_secret']);
        return new self(
            config::get('server_url'),
            config::get('shared_secret'),
            config::get('checksum_algorithm'),
            $forced,
        );
    }

    /**
     * Whether both a server URL and a shared secret are present.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return !empty($this->serverurl) && !empty($this->sharedsecret);
    }

    /**
     * A stable identifier of the server this connection points to.
     *
     * It is derived from the server URL only, never from the secret, so it is safe to use in cache
     * keys and to group work by server.
     *
     * @return string
     */
    public function get_id(): string {
        $serverurl = rtrim(trim($this->serverurl), '/');
        if (str_ends_with($serverurl, '/api')) {
            $serverurl = substr($serverurl, 0, -strlen('/api'));
        }
        return sha1(\core_text::strtolower($serverurl));
    }

    /**
     * Whether this connection points to the same server as another one.
     *
     * @param connection $other
     * @return bool
     */
    public function is_same_server(connection $other): bool {
        return $this->get_id() === $other->get_id();
    }
}
