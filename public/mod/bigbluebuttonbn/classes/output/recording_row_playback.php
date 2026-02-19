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

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_data;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\recording;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderer for recording row playback column
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 */
class recording_row_playback implements renderable, templatable {

    /**
     * @var $instance
     */
    protected $instance;

    /**
     * @var $recording
     */
    protected $recording;

    /**
     * recording_row_playback constructor.
     *
     * @param recording $rec
     * @param instance|null $instance $instance
     */
    public function __construct(recording $rec, ?instance $instance) {
        $this->instance = $instance ?? null;
        $this->recording = $rec;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $ispublished = $this->recording->get('published');
        $recordingid = $this->recording->get('id');
        $context = (object) [
            'dataimported' => $this->recording->get('imported'),
            'id' => 'playbacks-' . $this->recording->get('id'),
            'recordingid' => $recordingid,
            'additionaloptions' => '',
            'playbacks' => [],
        ];

        $playbacks = $this->recording->get('playbacks');
        if ($ispublished && $playbacks) {
            foreach ($playbacks as $playback) {
                if ($this->should_be_included($playback)) {
                    $linkattributes = [
                        'id' => "recording-play-{$playback['type']}-{$recordingid}",
                        'class' => 'btn btn-sm btn-default',
                        'data-action' => 'play',
                        'data-target' => $playback['type'],
                    ];
                    $stringid = 'view_recording_format_' . $playback['type'];
                    $stringtext = null;
                    try {
                        $stringtext = get_string($stringid, 'mod_bigbluebuttonbn');
                    } catch (\Exception $e) {
                        // Fallback if string does not exist.
                    }
                    if ($stringtext === null || $stringtext === $stringid) {
                        $stringtext = ucfirst($playback['type']);
                    }
                    $actionlink = new \action_link(
                        $playback['url'],
                        $stringtext,
                        null,
                        $linkattributes
                    );
                    $context->playbacks[] = $actionlink->export_for_template($output);
                }
            }
        }
        return $context;
    }
    /**
     * Helper function renders the link used for recording type in row for the data used by the recording table.
     *
     * @param array $playback
     * @return bool
     */
    protected function should_be_included(array $playback): bool {
        // All types that are not restricted are included.
        if (array_key_exists('restricted', $playback) && strtolower($playback['restricted']) == 'false') {
            return true;
        }

        $canmanagerecordings = roles::has_capability_in_course(
            $this->recording->get('courseid'), 'mod/bigbluebuttonbn:managerecordings');
        $canviewallformats = roles::has_capability_in_course(
            $this->recording->get('courseid'), 'mod/bigbluebuttonbn:viewallrecordingformats');
        $issafeformat = false;
        // Use the admin-configurable available formats if set, otherwise fallback to default.
        global $CFG;
        $availableformats = isset($CFG->bigbluebuttonbn_recording_safe_formats_options)
            ? array_map('trim', explode(',', $CFG->bigbluebuttonbn_recording_safe_formats_options))
            : ['notes', 'podcast', 'presentation', 'screenshare', 'statistics', 'video'];
        $safeformats = config::get('recording_safe_formats');
        if ($safeformats === '' || $safeformats === false || $safeformats === null) {
            $safeformats = 'presentation,video';
        }
        if ($safeformats) {
            $safeformatarray = str_getcsv($safeformats, escape: '\\');
            // Only allow safe formats that are in the available formats list.
            $safeformatarray = array_intersect($safeformatarray, $availableformats);
            $issafeformat = in_array($playback['type'], $safeformatarray);
        }
        return ($canmanagerecordings && $canviewallformats) || $issafeformat;
    }
}
