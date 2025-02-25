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
 * Extracts the recording ID from the action button's ID pattern.
 *
 * Example ID: `recording-publish-d6b2bf7998c4ebe9d2ccad0b09132a5d0b6b048d-1740088258056`
 *
 * @param {HTMLElement} element The clicked action button
 * @returns {string|null} The extracted recording ID or null
 */
const extractRecordingId = (element) => {
    const actionButton = element.closest('a.action-icon'); // Always get the <a> element
    if (!actionButton || !actionButton.id) {
        logMessage("Missing ID on action button.");
        return null;
    }

    const idPattern = /^recording-[^-]+-([\w\d]+)-/; // Extracts recording ID from <a> ID
    const match = actionButton.id.match(idPattern);

    if (!match) {
        logMessage("Failed to extract recording ID from button.");
        return null;
    }

    logMessage("Recording ID: " + match[1]);
    return match[1]; // Correct reference for extracted recording ID
};

/**
 * Extracts the action type dynamically from the `data-action` attribute.
 *
 * @param {HTMLElement} element The clicked action button
 * @returns {string|null} The action name (e.g., "publish", "delete") or null
 */
const extractActionType = (element) => {
    return element.dataset.action || null;
};

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


const requestPlainActionRefactored = async (element) => {
    const action = extractActionType(element);
    const recordingId = extractRecordingId(element);

    if (!action || !recordingId) {
        logMessage("Missing action type or recording ID.");
        return;
    }

    const payload = {
        recordingid: recordingId,
        action: action,
    };

    if (element.dataset.requireConfirmation === "1") {
        try {
            await saveCancelPromise(
                getString('confirm'),
                await getRecordingConfirmationMessage(payload),
                getString('ok', 'moodle'),
            );
        } catch {
            return; // User cancelled the action.
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

/**
 * Registers event listeners for recording actions in the plain HTML table.
 */
const registerPlainRecordingListeners = () => {
    document.addEventListener('click', (e) => {
        logMessage("DEBUG.");
        const actionButton = e.target.closest('.action-icon');
        if (actionButton) {
            e.preventDefault();
            requestPlainAction(actionButton);
        }
    });
};

// Initialize event listeners when the script is loaded
registerPlainRecordingListeners();

const logMessage = (message) => {
    if (typeof console !== "undefined" && typeof console.log === "function") {
        /* eslint-disable no-console */
        console.log(message);
        /* eslint-enable no-console */
    }
};