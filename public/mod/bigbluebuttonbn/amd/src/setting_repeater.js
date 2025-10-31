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
 * Repeater UI helper for admin_setting_configrepeater.
 *
 * @module     mod_bigbluebuttonbn/setting_repeater
 */
define(['jquery'], function($) {
    /**
     * Create a new repeater item from the stored template.
     *
     * @param {jQuery} root The root element.
     * @return {jQuery}
     */
    const createItem = function(root) {
        const templateHtml = root.find('[data-repeater-template]').html();
        return $(templateHtml);
    };

    /**
     * Ensure at least one item is always visible.
     *
     * @param {jQuery} items Container for the repeater items.
     */
    const ensureAtLeastOneItem = function(items) {
        if (!items.find('[data-repeater-item]').length) {
            items.append(createItem(items.closest('[data-repeater-root]')));
        }
    };

    /**
     * Initialise the repeater behaviour.
     *
     * @param {string} id The DOM id of the wrapper element.
     */
    const init = function(id) {
        const root = $('#' + id);
        if (!root.length) {
            return;
        }

        const items = root.find('[data-repeater-items]');
        ensureAtLeastOneItem(items);

        root.on('click', '[data-repeater-add]', function(e) {
            e.preventDefault();
            const newItem = createItem(root);
            newItem.find('input').val('');
            items.append(newItem);
        });

        root.on('click', '[data-repeater-remove]', function(e) {
            e.preventDefault();
            const item = $(this).closest('[data-repeater-item]');
            const visibleItems = items.find('[data-repeater-item]');
            if (visibleItems.length > 1) {
                item.remove();
            } else {
                item.find('input').val('');
            }
        });
    };

    return {
        init: init
    };
});
