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
 * @copyright  2025 Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
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

        return autocomplete.enhance.apply(autocomplete, args).then(function() {
            // Track insertion order of selected values.
            var selectionOrder = [];

            // Seed with currently selected options (preserves stored order from PHP).
            $select.find('option:selected').each(function() {
                selectionOrder.push(this.value);
            });

            // Find the pill container rendered by autocomplete.
            var $pillContainer = $select.parent().find('.form-autocomplete-selection');

            /**
             * Reorder both <option> elements and visible pills to match tracked order.
             * Called synchronously inside the change handler — pills already exist in
             * the DOM at that point, so there is no flicker.
             */
            var reorder = function() {
                // Reorder <option> elements (affects future renders).
                selectionOrder.forEach(function(val) {
                    var $opt = $select.find('option[value="' + val + '"]');
                    if ($opt.length) {
                        $select.append($opt);
                    }
                });
                // Reorder visible pills (fixes current render).
                if ($pillContainer.length) {
                    selectionOrder.forEach(function(val) {
                        var $pill = $pillContainer.find('[data-value="' + val + '"]');
                        if ($pill.length) {
                            $pillContainer.append($pill);
                        }
                    });
                }
            };

            // The change event fires AFTER updateSelectionList has rendered the pills,
            // so we can reorder them synchronously — no setTimeout, no flicker.
            $select.on('change', function() {
                var currentSelected = [];
                $select.find('option:selected').each(function() {
                    if (this.value) {
                        currentSelected.push(this.value);
                    }
                });

                // Remove deselected values.
                for (var i = selectionOrder.length - 1; i >= 0; i--) {
                    if (currentSelected.indexOf(selectionOrder[i]) === -1) {
                        selectionOrder.splice(i, 1);
                    }
                }

                // Append newly selected values at the end.
                currentSelected.forEach(function(val) {
                    if (selectionOrder.indexOf(val) === -1) {
                        selectionOrder.push(val);
                    }
                });

                reorder();
            });

            // Apply initial order.
            reorder();

            return;
        });
    };

    return {
        init
    };
});
