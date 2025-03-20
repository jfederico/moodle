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
use mod_bigbluebuttonbn\external\get_recordings_to_import;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable for the import page.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class import implements renderable, templatable {
    /**
     * @var instance $destinationinstance
     */
    protected $destinationinstance;

    /**
     * @var int|null $sourceinstanceid the source instance id or null if it is not yet set.
     */
    protected $sourceinstanceid;

    /**
     * @var int|null $sourcecourseid the source instance id or null if it is not yet set.
     */
    protected $sourcecourseid;

    /**
     * @var string $originpage the origin page.
     */
    protected $originpage;

    /**
     * @var string $originparams the origin params.
     */
    protected array $originparams;

    /**
     * import constructor.
     *
     * @param instance $destinationinstance
     * @param int $sourcecourseid
     * @param int $sourceinstanceid
     * @param string $originpage
     */
    public function __construct(instance $destinationinstance, int $sourcecourseid, int $sourceinstanceid, string $originpage = 'view', array $originparams = []) {
        $this->destinationinstance = $destinationinstance;
        $this->sourcecourseid = $sourcecourseid >= 0 ? $sourcecourseid : null;
        $this->sourceinstanceid = $sourceinstanceid >= 0 ? (int) $sourceinstanceid : 0;
        $this->originpage = $originpage;
        $this->originparams = $originparams;
    }

    /**
     * Defer to template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $courses = roles::import_get_courses_for_select($this->destinationinstance);
        if (config::get('importrecordings_from_deleted_enabled')) {
            $courses[0] = get_string('recordings_from_deleted_activities', 'mod_bigbluebuttonbn');
            ksort($courses);
        }
        $context = (object) [
            'bbbid' => $this->destinationinstance->get_instance_id(),
            'has_recordings' => true,
            'bbbsourceid' => 0,
            'recordings' => (object) [],
        ];

        if (!empty($this->sourceinstanceid)) {
            $context->sourceid = $this->sourceinstanceid;
            $context->search = [
                'value' => ''
            ];
            $sourceinstance = instance::get_from_instanceid($this->sourceinstanceid);
            if ($sourceinstance->is_type_room_only()) {
                $context->has_recordings = false;
            }
            $context->bbbsourceid = $sourceinstance->get_instance_id();
        }

        $actionurl = $this->destinationinstance->get_page_url('import', [
                'destbn' => $this->destinationinstance->get_instance_id(),
                'originpage' => $this->originpage,
                'originparams' => http_build_query($this->originparams),
            ]);

        // Now the selects.
        if (!empty($this->sourcecourseid)) {
            $selectrecords = [];

            $cms = get_fast_modinfo($this->sourcecourseid)->instances['bigbluebuttonbn'];
            foreach ($cms as $cm) {
                if ($cm->id == $this->destinationinstance->get_cm_id()) {
                    // Skip the target instance.
                    continue;
                }

                if ($cm->deletioninprogress) {
                    // Check if the BBB is not currently scheduled for deletion.
                    continue;
                }

                $selectrecords[$cm->instance] = $cm->name;
            }
            if (config::get('importrecordings_from_deleted_enabled')) {
                $selectrecords[0] =
                    get_string('recordings_from_deleted_activities', 'mod_bigbluebuttonbn');
            }
            $actionurl->param('sourcecourseid', $this->sourcecourseid);

            $select = new \single_select(
                $actionurl,
                'sourcebn',
                $selectrecords,
                $this->sourceinstanceid ?? ""
            );
            $context->bbb_select = $select->export_for_template($output);
        }
        $context->sourcecourseid = $this->sourcecourseid ?? 0;

        // Course selector.
        $context->course_select = (new \single_select(
            $actionurl,
            'sourcecourseid',
            $courses,
            $this->sourcecourseid ?? ""
        ))->export_for_template($output);

        if (!is_null($this->sourcecourseid)) {
            $context->has_selected_course = true;
        }

        if (!empty($this->sourcecourseid) && !empty($this->sourceinstanceid)) {
            try {
                $destinationinstanceid = $this->destinationinstance->get_instance_id();
                $sourcebigbluebuttonbnid = $this->sourceinstanceid;
                $sourcecourseid = $this->sourcecourseid;
                $tools = 'import';
                $groupid = null; // Adjust as needed.
        
                // Call the new external function
                $recordings = get_recordings_to_import::execute(
                    $destinationinstanceid,
                    $sourcebigbluebuttonbnid,
                    $sourcecourseid,
                    $tools,
                    $groupid
                );
        
                if (!empty($recordings['tabledata']['data'])) {
                    $recordingsoutput = json_decode($recordings['tabledata']['data'], true);
        
                    if (!empty($recordingsoutput)) {
                        $recordingsoutput[0]['first'] = true;
                    }
        
                    // Format dates properly.
                    foreach ($recordingsoutput as &$recording) {
                        if (!empty($recording['date'])) {
                            $recording['date'] = date('F j, Y, g:i A', $recording['date'] / 1000);
                        }
                    }
        
                    $context->recordings->output = $recordingsoutput;
                }
        
                // Handle warnings if any
                if (!empty($recordings['warnings'])) {
                    debugging('Warnings while fetching recordings: ' . print_r($recordings['warnings'], true));
                }
            } catch (\moodle_exception $e) {
                debugging('Error fetching recordings: ' . $e->getMessage());
            }
        }
        
        // Back button.
        $destinationurl = $this->destinationinstance->get_page_url($this->originpage, $this->originparams);
        $context->back_button = (new \single_button(
            $destinationurl,
            get_string('view_recording_button_return', 'mod_bigbluebuttonbn')
        ))->export_for_template($output);

        return $context;
    }
}
