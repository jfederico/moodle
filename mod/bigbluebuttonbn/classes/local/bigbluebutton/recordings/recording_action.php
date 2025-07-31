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

namespace mod_bigbluebuttonbn\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\logger;
use mod_bigbluebuttonbn\recording;
use mod_bigbluebuttonbn\local\config;

/**
 * Collection of helper methods for handling recordings actions in Moodle.
 *
 * Utility class for meeting actions
 *
 * @package mod_bigbluebuttonbn
 * @copyright 2021 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_action {
    /**
     * Import recording
     *
     * @param recording $recording
     * @param instance $targetinstance
     */
    public static function import(recording $recording, instance $targetinstance): void {
        $recording->create_imported_recording($targetinstance);
        // Log the recording imported event.
        logger::log_recording_imported_event(
            $targetinstance,
            $recording->get('id')
        );
    }

    /**
     * Helper for performing delete on recordings.
     *
     * @param recording $recording
     */
    public static function delete(recording $recording): void {
        // As the recordingid was not identified as imported recording link, execute delete on a real recording.
        // Step 1, delete imported links associated to the recording.
        $recordingstodelete = recording::get_records(['recordingid' => $recording->get('recordingid'),
            'imported' => true]);
        foreach ($recordingstodelete as $rec) {
            $rec->delete();
        }
        $recording->delete();
        // Log the recording deleted event.
        logger::log_recording_deleted_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }

    /**
     * Helper for performing edit on recordings.
     *
     * @param recording $recording
     */
    public static function edit(recording $recording): void {
        $recording->update();
        // Log the recording edited event.
        logger::log_recording_edited_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }

    /**
     * Helper for performing unprotect on recordings.
     *
     * @param recording $recording
     */
    public static function unprotect(recording $recording): void {
        if (!(boolean) config::get('recording_protect_editable')) {
            // Recording protect action through UI is disabled, there is no need to do anything else.
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'unprotect');
        }
        if ($recording->get('imported')) {
            // Imported recordings can not be unprotected. There is no need to do anything else.
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'unprotect');
        }
        $recording->set('protected', false);
        $recording->update();
        // Log the recording unprotected event.
        logger::log_recording_unprotected_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }

    /**
     * Helper for performing protect on recordings.
     *
     * @param recording $recording
     */
    public static function protect(recording $recording): void {
        if (!(boolean) config::get('recording_protect_editable')) {
            // Recording protect action through UI is disabled, there is no need to do anything else.
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'protect');
        }
        if ($recording->get('imported')) {
            // Imported recordings can not be unprotected. There is no need to do anything else.
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'protect');
        }
        $recording->set('protected', true);
        $recording->update();
        // Log the recording protected event.
        logger::log_recording_protected_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }

    /**
     * Helper for performing unpublish on recordings.
     *
     * @param recording $recording
     */
    public static function unpublish(recording $recording): void {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'unpublish');
        }
        $recording->set('published', false);
        $recording->update();
        // Log the recording unpublished event.
        logger::log_recording_unpublished_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }

    /**
     * Helper for performing publish on recordings.
     *
     * @param recording $recording
     */
    public static function publish(recording $recording): void {
        if ($recording->get('imported')) {
            /* Since the recording link is the one fetched from the BBB server, imported recordings can not be
             * unpublished. There is no need to do anything else.
             */
            throw new \moodle_exception('cannotperformaction', 'mod_bigblubuebuttobn', '', 'publish');
        }
        $recording->set('published', true);
        $recording->update();
        // Log the recording published event.
        logger::log_recording_published_event(
            instance::get_from_instanceid($recording->get('bigbluebuttonbnid')),
            $recording->get('id')
        );
    }
}
