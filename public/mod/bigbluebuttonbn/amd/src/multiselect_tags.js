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
 * @module     mod_bigbluebuttonbn/multiselect_tags
 * @copyright  2025 Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
define(['jquery', 'core/form-autocomplete'], function($, autocomplete) {
    /**
     * Initialise autocomplete and enforce capitalization for newly typed tags.
     *
     * @param {string} selector Selector for the original select element.
     * @return {Promise}
     */
    const init = function(selector) {
        const args = Array.prototype.slice.call(arguments);
        const $select = $(selector);

        return autocomplete.enhance.apply(autocomplete, args).then(function() {
            // Handler to normalize and check for duplicates.
            var processOptions = function() {
                // First pass: collect all existing normalized values from preset options.
                var existingValues = {};
                $select.find('option:not([data-iscustom])').each(function() {
                    var val = $(this).attr('value');
                    if (val) {
                        existingValues[val.toLowerCase()] = true;
                    }
                });

                // Second pass: process custom options and check for duplicates.
                var toRemove = [];
                $select.find('option[data-iscustom]').each(function() {
                    var $opt = $(this);
                    var val = $opt.attr('value');
                    if (!val) {
                        return;
                    }

                    var normalized = val.toLowerCase();

                    // Check if this normalized value already exists.
                    if (existingValues[normalized]) {
                        // Mark for removal.
                        toRemove.push({opt: $opt, val: val});
                        return;
                    }

                    // Mark this value as seen for subsequent custom options.
                    existingValues[normalized] = true;

                    var display = normalized.replace(/[_-]/g, ' ').replace(/\s+/g, ' ').trim();
                    display = display.replace(/\b\w/g, function(c) {
                        return c.toUpperCase();
                    });

                    $opt.attr('value', normalized);
                    $opt.text(display);

                    // Update the visible pill label to match the capitalized display text.
                    var $pill = $select.parent().find('.form-autocomplete-selection [data-value="' + val + '"]');
                    if ($pill.length) {
                        $pill.attr('data-value', normalized);
                        var $textNodes = $pill.contents().filter(function() {
                            return this.nodeType === 3;
                        });
                        // Use the last text node (label) to avoid touching leading whitespace.
                        var $labelNode = $textNodes.last();
                        if ($labelNode.length) {
                            $labelNode[0].nodeValue = ' ' + display;
                        }
                    }
                });

                // Remove duplicates after iteration to avoid modifying collection during iteration.
                toRemove.forEach(function(item) {
                    item.opt.remove();
                    $select.parent().find('.form-autocomplete-selection [data-value="' + item.val + '"]').remove();
                });
            };

            // Use both change event and a slight delay to catch additions quickly.
            $select.on('change', function() {
                // Use setTimeout with 0 to run after the current call stack clears.
                setTimeout(processOptions, 0);
            });

            // Also process immediately in case there are existing values.
            processOptions();

            return $select;
        });
    };

    return {init};
});
