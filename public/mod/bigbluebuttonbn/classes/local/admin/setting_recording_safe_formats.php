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

use admin_setting_configmultiselect;

/**
 * Multiselect setting that allows arbitrary recording format values.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_recording_safe_formats extends admin_setting_configmultiselect {

    /**
     * Constructor.
     *
     * @param string $name Internal setting name.
     * @param string $visiblename Display name.
     * @param string $description Help text.
     * @param array $defaultsetting Default selected values.
     * @param array $choices Initial list of supported formats.
     */
    public function __construct(string $name, string $visiblename, string $description,
            array $defaultsetting, array $choices) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);
        $this->add_configured_choices();
    }

    /**
     * Ensure choices include any values stored in configuration.
     */
    protected function add_configured_choices(): void {
        $stored = get_config('mod_bigbluebuttonbn', 'recording_safe_formats');
        if (!$stored) {
            return;
        }
        if (strpbrk($stored, "\r\n") !== false) {
            $migrated = implode(',', $this->normalise_values($stored));
            if ($migrated !== $stored) {
                $this->config_write($this->name, $migrated);
                $stored = $migrated;
            }
        }
        foreach ($this->normalise_values($stored) as $value) {
            if (!array_key_exists($value, $this->choices)) {
                $this->choices[$value] = $value;
            }
        }
    }

    /**
     * Convert a CSV string into a list of trimmed values.
     *
     * @param string $raw
     * @return array
     */
    protected function normalise_values(string $raw): array {
        if ($raw === '') {
            return [];
        }

        $raw = str_replace(["\r\n", "\r", "\n"], ',', $raw);
        $values = array_map('trim', explode(',', $raw));
        $values = array_filter($values, static function(string $value): bool {
            return $value !== '';
        });

        return array_values(array_unique($values));
    }

    /**
     * Save submitted values allowing new entries beyond predefined choices.
     *
     * @param mixed $data
     * @return string
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $cleanvalues = [];
        foreach ($data as $value) {
            $value = clean_param(trim((string) $value), PARAM_ALPHANUMEXT);
            if ($value === '') {
                continue;
            }
            $cleanvalues[$value] = $value;
        }

        $stored = implode(',', array_keys($cleanvalues));
        $result = $this->config_write($this->name, $stored) ? '' : get_string('errorsetting', 'admin');

        if ($result === '') {
            foreach ($cleanvalues as $value) {
                if (!array_key_exists($value, $this->choices)) {
                    $this->choices[$value] = $value;
                }
            }
        }

        return $result;
    }
}
