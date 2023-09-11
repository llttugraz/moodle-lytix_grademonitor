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
 * Plugin version info
 * @package    lytix_grademonitor
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2023091100; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2021051700; // Requires this Moodle version 3.11.
$plugin->component = 'lytix_grademonitor'; // Full name of the plugin.
$plugin->dependencies = [
    'lytix_helper' => ANY_VERSION,
    'lytix_logs' => ANY_VERSION,
    'lytix_config' => ANY_VERSION
];
$plugin->release   = 'v1.0.6';
$plugin->maturity  = MATURITY_STABLE;
$plugin->supported = [401, 401];
