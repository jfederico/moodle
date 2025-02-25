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

namespace mod_bigbluebuttonbn\output;

use core\check\result;
use core\output\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\external\get_recordings;
use mod_bigbluebuttonbn\local\config;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use tool_task\check\cronrunning;

/**
 * View Page template renderable for recordings only.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 onwards
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class view_page_recordings_plain implements renderable, templatable {

    /** @var instance The instance being rendered */
    protected $instance;

    /**
     * Constructor for the View Page.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Export the content required to render the template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {

        $templatedata = (object) [
            'instanceid' => $this->instance->get_instance_id(),
            'recordingsoutput' => [], // Initialize recordings array.
            'headers' => [
                'playback' => get_string('view_recording_playback', 'mod_bigbluebuttonbn'),
                'name' => get_string('view_recording_name', 'mod_bigbluebuttonbn'),
                'description' => get_string('view_recording_description', 'mod_bigbluebuttonbn'),
                'preview' => get_string('view_recording_preview', 'mod_bigbluebuttonbn'),
                'date' => get_string('view_recording_list_date', 'mod_bigbluebuttonbn'),
                'duration' => get_string('view_recording_duration', 'mod_bigbluebuttonbn'),
                'actionbar' => get_string('view_recording_actionbar', 'mod_bigbluebuttonbn'),
            ]
        ];

        // Check if cron is running and add warnings if necessary.
        $templatedata->recordingwarnings = [];
        $check = new cronrunning();
        $result = $check->get_result();
        if ($result->get_status() != result::OK && $this->instance->is_moderator()) {
            $templatedata->recordingwarnings[] = (new notification(
                get_string('view_message_cron_disabled', 'mod_bigbluebuttonbn',
                    $result->get_summary()),
                notification::NOTIFY_ERROR,
                false
            ))->export_for_template($output);
        }

        $recordings = new recordings_session($this->instance);
        $templatedata->recordings = $recordings->export_for_template($output);

        try {
            $recordings = get_recordings::execute($this->instance->get_instance_id());

            if (!empty($recordings['tabledata']['data'])) {
                $recordingsoutput = json_decode($recordings['tabledata']['data'], true);

                // Mark the first recording as first.
                if (!empty($recordingsoutput)) {
                    $recordingsoutput[0]['first'] = true;
                }

                // Format date before passing it to Mustache.
                foreach ($recordingsoutput as &$recording) {
                    if (!empty($recording['date'])) {
                        $recording['date'] = date('F j, Y, g:i A', $recording['date'] / 1000); // Convert milliseconds to seconds.
                    }
                }

                // Only pass the first 10 recordings
                $templatedata->recordingsoutput = array_slice($recordingsoutput, 0, 10);
            }
        } catch (\moodle_exception $e) {
            debugging('Error fetching recordings: ' . $e->getMessage());
        }

        return $templatedata;
    }

    /**
     * Whether to show the view warning.
     *
     * @return bool
     */
    protected function show_view_warning(): bool {
        if ($this->instance->is_admin()) {
            return true;
        }

        $generalwarningroles = explode(',', config::get('general_warning_roles'));
        $userroles = \mod_bigbluebuttonbn\local\helpers\roles::get_user_roles(
            $this->instance->get_context(),
            $this->instance->get_user_id()
        );

        foreach ($userroles as $userrole) {
            if (in_array($userrole->shortname, $generalwarningroles)) {
                return true;
            }
        }

        return false;
    }

}
