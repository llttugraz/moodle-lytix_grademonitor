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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    lytix_grademonitor
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
    'local_lytix_lytix_grademonitor_grademonitor_get'   => array(
        'classname'   => 'lytix_grademonitor\\grademonitor_lib',
        'methodname'  => 'grademonitor_get',
        'description' => 'Provides data for the grademonitor widget',
        'type'        => 'write',
        'ajax'        => 'true'
    ),
    'local_lytix_lytix_grademonitor_grademonitor_get_table' => array(
        'classname'   => 'lytix_grademonitor\\grademonitor_lib',
        'methodname'  => 'grademonitor_get_table',
        'description' => 'Provides data for the grademonitor widget',
        'type'        => 'read',
        'ajax'        => 'true'
    ),
    'local_lytix_lytix_grademonitor_grademonitor_get_history' => array(
        'classname'   => 'lytix_grademonitor\\grademonitor_lib',
        'methodname'  => 'grademonitor_get_history',
        'description' => 'Provides data for the grademonitor widget',
        'type'        => 'read',
        'ajax'        => 'true'
    ),
);
