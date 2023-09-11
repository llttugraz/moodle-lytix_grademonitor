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
 * Upgrade changes between versions
 *
 * @package   lytix_grademonitor
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laterB
 */

/**
 * Upgrade Grademonitor Basic DB
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_lytix_grademonitor_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022032800) {
        // New service added.
        // Basic savepoint reached.
        upgrade_plugin_savepoint(true, 2022032800, 'lytix', 'grademonitor');
    }

    if ($oldversion < 2022062800) {
        // New service added.
        // Basic savepoint reached.
        upgrade_plugin_savepoint(true, 2022062800, 'lytix', 'grademonitor');
    }

    if ($oldversion < 2022072500) {
        // New service added.
        // Basic savepoint reached.
        upgrade_plugin_savepoint(true, 2022072500, 'lytix', 'grademonitor');
    }

    if ($oldversion < 2022092100) {
        // New service added.
        // Basic savepoint reached.
        upgrade_plugin_savepoint(true, 2022092100, 'lytix', 'grademonitor');
    }

    return true;
}

