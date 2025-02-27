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

/**
 * Utility module for logging.
 *
 * @module     mod_bigbluebuttonbn/_utils
 * @copyright  2025 Blindside Networks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Logs a message to the console for debugging.
 *
 * @param {string|object} message - The message to log.
 */
export const logMessage = (message) => {
    if (typeof console !== "undefined" && typeof console.log === "function") {
        /* eslint-disable no-console */
        console.log(message);
        /* eslint-enable no-console */
    }
};
