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

namespace mod_bigbluebuttonbn\local\admin;

defined('MOODLE_INTERNAL') || die();

use admin_setting;

/**
 * Admin setting that stores a repeated set of free-text values.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_configrepeater extends admin_setting {
    /** @var int Sanitising rule applied to each value. */
    protected int $entryparam;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $defaultsetting Comma separated list of defaults.
     * @param int $paramtype Cleaning rule for individual entries.
     */
    public function __construct(string $name, string $visiblename, string $description,
            string $defaultsetting = '', int $paramtype = PARAM_TEXT) {
    parent::__construct($name, $visiblename, $description, $defaultsetting);
    $this->entryparam = $paramtype;
    }

    /**
     * Return the persisted setting as stored in config.
     *
     * @return string|null
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Split a stored string into individual values.
     *
     * @param string|null $data
     * @return array
     */
    protected function split_values(?string $data): array {
        if ($data === null || $data === '') {
            return [];
        }
        $parts = array_map('trim', explode(',', $data));
        return array_values(array_filter($parts, static function(string $value): bool {
            return $value !== '';
        }));
    }

    /**
     * Save the submitted values as a comma-separated string.
     *
     * @param mixed $data
     * @return string Empty string on success, else error identifier.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return get_string('errorsetting', 'admin');
        }

        $cleanvalues = [];
        foreach ($data as $value) {
            $value = clean_param(trim((string) $value), $this->entryparam);
            if ($value === '') {
                continue;
            }
            $cleanvalues[] = $value;
        }

        $stored = implode(',', $cleanvalues);
        return $this->config_write($this->name, $stored) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Render the HTML for the admin setting.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT, $PAGE;

        $values = [];
        if (is_array($data)) {
            $values = array_values($data);
        } else if (is_string($data)) {
            $values = $this->split_values($data);
        } else if (($stored = $this->get_setting()) !== null) {
            $values = $this->split_values($stored);
        }

        // Ensure the form always displays at least one empty row.
        if (empty($values)) {
            $values = [''];
        }

        $defaultinfo = get_string('none');
        $defaultsetting = $this->get_defaultsetting();
        if (!empty($defaultsetting)) {
            $defaultvalues = $this->split_values($defaultsetting);
            if (!empty($defaultvalues)) {
                $defaultinfo = implode(', ', $defaultvalues);
            }
        }

        $context = (object) [
            'id' => $this->get_id(),
            'fieldname' => $this->get_full_name(),
            'values' => $values,
            'addlabel' => get_string('setting_repeater_add', 'mod_bigbluebuttonbn'),
            'removelabel' => get_string('setting_repeater_remove', 'mod_bigbluebuttonbn'),
        ];

        $element = $OUTPUT->render_from_template('mod_bigbluebuttonbn/setting_repeater', $context);
        $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/setting_repeater', 'init', [$this->get_id()]);

        return format_admin_setting($this, $this->visiblename, $element, $this->description,
            false, '', $defaultinfo, $query);
    }
}
