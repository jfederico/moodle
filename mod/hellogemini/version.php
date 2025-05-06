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
 * Version details for the hellogemini module.
 *
 * @package    mod_hellogemini
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_hellogemini'; // Full name of the plugin (used for diagnostics).
$plugin->version   = 2025050600;        // YYYYMMDDHH (year, month, day, 24-hr time).
$plugin->requires  = 2022112800;        // Requires Moodle 4.1 (adjust as per your Moodle version, e.g., 2023042400 for Moodle 4.2).
$plugin->maturity  = MATURITY_ALPHA;    // How stable the plugin is (MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE).
$plugin->release   = 'v0.1';            // Human-readable version name.
