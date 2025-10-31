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
 * Helper that hides the fallback select and delegates to the autocomplete enhancer.
 *
 * @module     mod_bigbluebuttonbn/setting_configmultiselect_tags
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/form-autocomplete'], function($, autocomplete) {
    /**
     * Initialise the tag-enabled multiselect.
     *
     * @param {string} selector Selector for the original select element.
     * @return {Promise}
     */
    const init = function(selector) {
        const args = Array.prototype.slice.call(arguments);
        const $select = $(selector);

        if ($select.length) {
            $select.attr('data-bbb-tags-enhanced', '1');
        }

        return autocomplete.enhance.apply(autocomplete, args);
    };

    return {
        init
    };
});
