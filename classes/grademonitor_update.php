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

use lytix_grademonitor\grademonitor_helper;

// @codingStandardsIgnoreLine
require('../../../../../config.php');

require_login();

global $USER, $CFG;

$data = file_get_contents('php://input');

$serialiseddata = json_decode($data);
$success = false;

if ($serialiseddata) {
    $courseid = $serialiseddata->courseid;
    $serialiseddata = $serialiseddata->changes;

    $record = grademonitor_helper::get_grademonitor_info($USER->id, $courseid);

    if (property_exists($serialiseddata, 'goal')) {
        $record->goal = (int)$serialiseddata->goal;
    }

    if (property_exists($serialiseddata, 'checked')) {
        $old = json_decode($record->estimations);

        $tmp = $old->CheckedIndexes;
        foreach ($serialiseddata->checked as $key => $checked) {
            foreach ($tmp as $index) {
                if ($key == $index->id) {
                    $index->checked = $checked;
                }
            }
        }
        $old->CheckedIndexes = $tmp;

        $new = [
            'Estimations' => $old->Estimations,
            'CheckedIndexes' => $old->CheckedIndexes
        ];
        $record->estimations = json_encode($new);
    }

    if (property_exists($serialiseddata, 'estimations')) {
        $old = json_decode($record->estimations);

        $tmp = $old->Estimations;
        foreach ($serialiseddata->estimations as $key => $estimation) {
            foreach ($tmp as $est) {
                if ($key == $est->id) {
                    $est->est = $estimation;
                }
            }
        }
        $old->Estimations = $tmp;

        $new = [
            'Estimations' => $old->Estimations,
            'CheckedIndexes' => $old->CheckedIndexes
        ];
        $record->estimations = json_encode($new);
    }

    if (property_exists($serialiseddata, "schemeUpdateSeen")) {
        $record->dismiss_notification = (int)$serialiseddata->schemeUpdateSeen;
    }

    if (property_exists($serialiseddata, "showAverage")) {
        $record->show_others = (int)$serialiseddata->showAverage;
    }

    $success = grademonitor_helper::set_grademonitor_info($USER->id, $courseid, $record);
}
