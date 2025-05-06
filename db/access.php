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

/**
 * Capability definitions for mod_hellodeepcode plugin.
 *
 * @package    mod_hellodeepcode
 * @copyright 2024 Deepcode AI (contact@deepcode.ai)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    // Capability to view instances of this module
    'mod/hellodeepcode:view' => array(
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'defaultvalue' => true,
    ),

    // Capability to add new instances of this module
    'mod/hellodeepcode:addinstance' => array(
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'defaultvalue' => false,
    ),
);
