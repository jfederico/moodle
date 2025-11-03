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

use admin_setting_configmultiselect;

defined('MOODLE_INTERNAL') || die();

/**
 * Multiselect admin setting that allows typing arbitrary values via the autocomplete widget.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2025 Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class setting_configmultiselect_tags extends admin_setting_configmultiselect {
    /** @var string Placeholder shown in the enhanced select. */
    protected string $placeholder;
    /** @var bool Whether to show suggestions immediately. */
    protected bool $showsuggestions;
    /** @var string Text displayed when no selections exist. */
    protected string $noselectionstring;

    /**
     * Constructor.
     *
     * @param string $name Setting name.
     * @param string $visiblename Visible setting name.
     * @param string $description Setting description.
     * @param array $defaultsetting Default values.
     * @param array $choices Preset choices.
     * @param string $placeholder Placeholder string.
     * @param string $noselectionstring String displayed when there are no selections.
     * @param bool $showsuggestions Whether to show suggestions on focus.
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        array $defaultsetting,
        array $choices,
        string $placeholder,
        string $noselectionstring,
        bool $showsuggestions = true
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);
        $this->placeholder = $placeholder;
        $this->showsuggestions = $showsuggestions;
        $this->noselectionstring = $noselectionstring;
    }

    /**
     * Ensure choices include any stored values so they remain visible.
     *
     * @return bool
     */
    public function load_choices() {
        $loaded = parent::load_choices();
        if (!$loaded) {
            return false;
        }
        $current = $this->config_read($this->name);
        if (!empty($current)) {
            $values = array_filter(array_map('trim', explode(',', $current)), static function(string $value): bool {
                return $value !== '';
            });
            foreach ($values as $value) {
                if (!array_key_exists($value, $this->choices)) {
                    $this->choices[$value] = $this->resolve_label($value);
                }
            }
        }
        return true;
    }

    /**
     * Persist the selected values, allowing arbitrary entries.
     *
     * @param array $data
     * @return string
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }
        unset($data['xxxxx']);

        $values = [];
        foreach ($data as $value) {
            $value = trim(clean_param($value, PARAM_ALPHANUMEXT));
            if ($value === '') {
                continue;
            }
            $values[$value] = $value;
        }

        $stored = implode(',', array_keys($values));
        return $this->config_write($this->name, $stored) ? '' : get_string('errorsetting', 'admin');
    }

    /**
     * Resolve the label for a value, preferring existing language strings.
     *
     * @param string $value
     * @return string
     */
    protected function resolve_label(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $component = 'mod_bigbluebuttonbn';
        $stringmanager = get_string_manager();
        $candidates = [
            'view_recording_format_' . $value,
            $value,
        ];

        foreach ($candidates as $identifier) {
            if ($stringmanager->string_exists($identifier, $component)) {
                return get_string($identifier, $component);
            }
        }

        $formatted = str_replace(['_', '-'], ' ', $value);
        $formatted = preg_replace('/\s+/', ' ', $formatted);
        $formatted = trim($formatted);

        return $formatted === '' ? $value : ucwords($formatted);
    }

    /**
     * Render the autocomplete-enhanced multiselect input.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        $html = parent::output_html($data, $query);
        if ($html === '') {
            return $html;
        }

        if ($this->is_readonly()) {
            return $html;
        }

        if (strpos($html, 'data-bbb-tags-select="1"') === false) {
            $html = preg_replace('/<select\b/', '<select data-bbb-tags-select="1"', $html, 1);
        }

        $params = [
            '#' . $this->get_id(),
            true,
            '',
            $this->placeholder,
            false,
            $this->showsuggestions,
            $this->noselectionstring,
        ];
        $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/setting_configmultiselect_tags', 'init', $params);

        return $html;
    }
}
