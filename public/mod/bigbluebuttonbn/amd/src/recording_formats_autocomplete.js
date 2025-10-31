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
 * Adds autocomplete behaviour to the safe recording formats multiselect.
 *
 * @module     mod_bigbluebuttonbn/recording_formats_autocomplete
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/form-autocomplete'], function(Autocomplete) {
    /**
     * Enhance the select element with tag-like multi entry behaviour.
     *
     * @param {string} selector Selector that targets the original select element.
     * @return {Promise|undefined}
     */
    const init = function(selector) {
        if (!selector) {
            return undefined;
        }

        const element = document.querySelector(selector);
        if (!element) {
            return undefined;
        }

        // Enable tagging mode so admins can type custom formats.
        return Autocomplete.enhance(selector, true);
    };

    return {
        init
    };
});
