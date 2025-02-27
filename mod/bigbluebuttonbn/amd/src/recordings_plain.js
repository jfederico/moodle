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
 * JS for handling actions in the plain recordings table.
 *
 * @module      mod_bigbluebuttonbn/recordings_plain
 * @copyright   2025 Blindside Networks Inc
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as repository from './repository';
import { exception as displayException, saveCancelPromise } from 'core/notification';
import { getString } from 'core/str';

/**
 * Handles an action (e.g., delete, publish, unpublish, lock, etc.) for a recording.
 *
 * @param {HTMLElement} element The clicked action button
 * @returns {Promise}
 */
const requestPlainAction = async(element) => {
    const getDataFromAction = (element, dataType) => {
        const dataElement = element.closest(`[data-${dataType}]`);
        if (dataElement) {
            return dataElement.dataset[dataType];
        }

        return null;
    };

    const elementData = element.dataset;
    const payload = {
        bigbluebuttonbnid: getDataFromAction(element, 'bbbid'),
        recordingid: getDataFromAction(element, 'recordingid'),
        additionaloptions: getDataFromAction(element, 'additionaloptions'),
        action: elementData.action,
    };
    logMessage(payload);

    // Slight change for import, for additional options.
    if (!payload.additionaloptions) {
        payload.additionaloptions = {};
    }
    if (elementData.action === 'import') {
        const bbbsourceid = getDataFromAction(element, 'source-instance-id');
        const bbbcourseid = getDataFromAction(element, 'source-course-id');
        if (!payload.additionaloptions) {
            payload.additionaloptions = {};
        }
        payload.additionaloptions.sourceid = bbbsourceid ? bbbsourceid : 0;
        payload.additionaloptions.bbbcourseid = bbbcourseid ? bbbcourseid : 0;
    }
    // Now additional options should be a json string.
    payload.additionaloptions = JSON.stringify(payload.additionaloptions);
    if (element.dataset.requireConfirmation === "1") {
        // Create the confirmation dialogue.
        try {
            await saveCancelPromise(
                getString('confirm'),
                await getRecordingConfirmationMessage(payload),
                getString('ok', 'moodle'),
            );
        } catch {
            // User cancelled the dialogue.
            return;
        }
    }

    return repository.updateRecording(payload)
        .then(() => refreshPlainTable())
        .catch(displayException);
};

/**
 * Generates a confirmation message for recording actions.
 *
 * @param {Object} data The recording action data
 * @returns {Promise<string>}
 */
const getRecordingConfirmationMessage = async (data) => {

    logMessage(data);

    const playbackElement = document.querySelector(`#playbacks-${data.recordingid}`);

    if (!playbackElement) {
        // Fallback if the element is missing
        return getString(`view_recording_${data.action}_confirmation`, 'bigbluebuttonbn');
    }

    // Determine recording type (imported or regular)
    const recordingType = await getString(
        playbackElement.dataset.imported === 'true' ? 'view_recording_link' : 'view_recording',
        'bigbluebuttonbn'
    );

    // Get base confirmation message
    const confirmation = await getString(
        `view_recording_${data.action}_confirmation`,
        'bigbluebuttonbn',
        recordingType
    );

    if (data.action === 'import') {
        return confirmation; // No additional warnings needed
    }

    // Handle associated links
    const associatedLinkCount = document.querySelector(`a#recording-${data.action}-${data.recordingid}`)?.dataset?.links;

    if (!associatedLinkCount || associatedLinkCount === "0") {
        return confirmation; // No warnings needed
    }

    // Fetch warning message based on link count
    const confirmationWarning = await getString(
        associatedLinkCount === "1"
            ? `view_recording_${data.action}_confirmation_warning_p`
            : `view_recording_${data.action}_confirmation_warning_s`,
        'bigbluebuttonbn',
        associatedLinkCount
    );

    return `${confirmationWarning}\n\n${confirmation}`;
};

/**
 * Refreshes the plain recordings table by reloading the page.
 */
const refreshPlainTable = () => {
    window.location.reload(); // Refresh page to update the table
};

const registerPlainRecordingListeners = () => {
    document.addEventListener('click', (e) => {
        logMessage("registerPlainRecordingListeners.");
        const actionButton = e.target.closest('.action-icon');
        if (actionButton) {
            e.preventDefault();
            requestPlainAction(actionButton);
            return;
        }

        // Detect sortable column click
        const sortableHeader = e.target.closest(".sortable-header");
        if (sortableHeader) {
            const column = sortableHeader.dataset.sort;
            e.preventDefault();
            sortTable(column);
        }
    });
};

registerPlainRecordingListeners();

// Logging utility.
const logMessage = (message) => {
    if (typeof console !== "undefined" && typeof console.log === "function") {
        /* eslint-disable no-console */
        console.log(message);
        /* eslint-enable no-console */
    }
};

// Sorting functionality
let sortOrders = { name: true, description: true, date: true }; // Track sorting state for each column

const sortTable = (column) => {
    const tableContainer = document.querySelector(".mod_bigbluebuttonbn_recordings_table_plain");

    if (!tableContainer) {
        return;
    }

    const rows = Array.from(tableContainer.querySelectorAll(".row.mb-3.align-items-center"));

    rows.sort((rowA, rowB) => {
        let valueA, valueB;

        if (column === "date") {
            const dateAElement = rowA.querySelector(".col-md-2[data-sort='date']");
            const dateBElement = rowB.querySelector(".col-md-2[data-sort='date']");

            if (!dateAElement || !dateBElement) {
                return 0;
            }

            const dateA = parseDate(dateAElement.textContent.trim());
            const dateB = parseDate(dateBElement.textContent.trim());

            return sortOrders[column] ? dateA - dateB : dateB - dateA;
        } else {
            const columnSelector = `.col-md-${column === "name" ? 1 : 2}[data-sort='${column}']`;
            const elementA = rowA.querySelector(columnSelector);
            const elementB = rowB.querySelector(columnSelector);

            if (!elementA || !elementB) {
                return 0;
            }

            valueA = elementA.textContent.trim().toLowerCase();
            valueB = elementB.textContent.trim().toLowerCase();

            return sortOrders[column] ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
        }
    });

    rows.forEach(row => {
        tableContainer.appendChild(row);
    });

    sortOrders[column] = !sortOrders[column];

    updateSortIcons(column);
};

const parseDate = (dateString) => {
    const parsedDate = Date.parse(dateString);
    return isNaN(parsedDate) ? 0 : parsedDate; // Convert to timestamp for comparison
};

const updateSortIcons = (activeColumn) => {
    document.querySelectorAll(".sortable-header .sort-icon").forEach(icon => {
        icon.textContent = "▲"; // Reset all icons to default ascending
    });

    const activeHeader = document.querySelector(`.sortable-header[data-sort="${activeColumn}"] .sort-icon`);

    if (activeHeader) {
        activeHeader.textContent = sortOrders[activeColumn] ? "▲" : "▼";
    }
};
