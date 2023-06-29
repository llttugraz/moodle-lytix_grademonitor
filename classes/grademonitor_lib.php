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
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace lytix_grademonitor;

defined('MOODLE_INTERNAL') || die();

use lytix_config\render_view;
use lytix_helper\calculation_helper;
use lytix_helper\course_settings;
use lytix_grademonitor\grademonitor_history;

require_once("$CFG->libdir/gradelib.php");
require_once("$CFG->dirroot/grade/querylib.php");

/**
 * Class grademonitor_lib
 */
class grademonitor_lib extends \external_api {

    /**
     * Checks parameters.
     * @return \external_function_parameters
     */
    public static function grademonitor_get_parameters() {
        return new \external_function_parameters(
            [
                'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Checks return values.
     *
     * @return \external_single_structure
     */
    public static function grademonitor_get_returns() {
        return new \external_single_structure(
            [
                'Items' => new \external_single_structure(
                  [
                      'IDs' => new \external_multiple_structure(
                          new \external_value(PARAM_INT, 'IDs of the grade items', VALUE_OPTIONAL)
                      ),
                      'Names' => new \external_multiple_structure(
                          new \external_value(PARAM_TEXT, 'Names of the grade items', VALUE_OPTIONAL)
                      ),
                      'MaxScores' => new \external_multiple_structure(
                          new \external_value(PARAM_FLOAT, 'Max points to get for the grade items', VALUE_OPTIONAL)
                      ),
                      'Scores' => new \external_multiple_structure(
                          new \external_value(PARAM_FLOAT, 'Points got for the grade items', VALUE_OPTIONAL)
                      ),
                      'ClassAvgs' => new \external_multiple_structure(
                          new \external_value(PARAM_FLOAT, 'Avg points achieved for the grade item', VALUE_OPTIONAL)
                      ),
                      'Estimations' => new \external_multiple_structure(
                          new \external_value(PARAM_INT, 'Estimated percentage for the items', VALUE_OPTIONAL)
                      ),
                      'OptionalIndexes' => new \external_multiple_structure(
                          new \external_value(PARAM_INT, 'Index of optional items', VALUE_OPTIONAL)
                      ),
                      'CheckedIndexes' => new \external_multiple_structure(
                          new \external_value(PARAM_INT, 'Index of checked & not graded items', VALUE_OPTIONAL)
                      )
                  ], 'grade items array', VALUE_OPTIONAL),
                'Goal'  => new \external_value(PARAM_INT, 'Goal for the coursegrade', VALUE_OPTIONAL),
                'Scheme' => new \external_multiple_structure(
                    new \external_value(PARAM_FLOAT, 'Percentage needed for the grades', VALUE_OPTIONAL)
                ),
                'LastSchemeUpdate' => new \external_value(PARAM_INT, 'Timestamp of change', VALUE_OPTIONAL),
                'ShowAverage' => new \external_value(PARAM_BOOL, 'Show others grades', VALUE_OPTIONAL)
            ]
        );
    }

    /**
     * Get the data from the database.
     *
     * @param int $contextid
     * @param int $courseid
     * @return array
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function grademonitor_get($contextid, $courseid): array {
        global $USER;
        $params = self::validate_parameters(self::grademonitor_get_parameters(), [
            'contextid' => $contextid,
            'courseid'  => $courseid
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        if (!render_view::is_student($context, $USER->id)) {
            return [
                'Items' => [
                    'IDs' => [],
                    'Names' => [],
                    'MaxScores' => [],
                    'Scores' => [],
                    'ClassAvgs' => [],
                    'Estimations' => [],
                    'OptionalIndexes' => [],
                    'CheckedIndexes' => []
                ],
                'Goal' => 0,
                'Scheme' => [],
                'LastSchemeUpdate' => 0,
                'ShowAverage' => false
            ];
        }

        $items = grademonitor_helper::get_course_items($courseid);
        $grades = grademonitor_helper::get_course_grades($courseid);

        $ids = [];
        $names = [];
        $maxscores = [];
        $scores = [];
        $classavgs = [];

        foreach ($items as $item) {
            $ids[] = (int)$item->id;
            $names[] = (string)$item->itemname;
            $maxscores[] = (float)$item->grademax;

            $itemcnt = 0;
            $itemsum = 0;
            foreach ($grades as $grade) {
                if ($item->id == $grade->itemid) {
                    $itemsum += (float)$grade->finalgrade;
                    $itemcnt++;
                    if ($grade->aggregationstatus == 'extra') {
                        $item->itemtype = 'bonus';
                    }
                    if ($grade->userid == $USER->id) {
                        $scores[] = (float)$grade->finalgrade;
                    }
                }
            }
            $classavgs[] = $itemcnt ? ($itemsum / $itemcnt) : 0;
        }

        $index = 0;
        $optionalindexes = [];
        foreach ($items as $item) {
            if (isset($item->itemtype) && $item->itemtype == 'bonus') {
                $optionalindexes[] = $index;
            }
            $index++;
        }

        $info = grademonitor_helper::get_grademonitor_info($USER->id, $courseid);
        $record = $info;
        $options = json_decode($info->estimations);

        $estimations = [];
        foreach ($options->Estimations as $estimation) {
            $estimations[] = $estimation->est;
        }

        $checkedindexes = [];
        foreach ($options->CheckedIndexes as $checked) {
            if ($checked->checked) {
                $checkedindexes[] = $checked->pos;
            }
        }

        $result = array();
        $result['Items'] = [
            'IDs' => $ids,
            'Names' => $names,
            'MaxScores' => $maxscores,
            'Scores' => $scores,
            'ClassAvgs' => $classavgs,
            'Estimations' => $estimations,
            'OptionalIndexes' => $optionalindexes,
            'CheckedIndexes' => $checkedindexes
        ];

        $result['Goal'] = $record->goal;
        $result['Scheme'] = grademonitor_helper::get_course_scheme($contextid);
        if (!$record->dismiss_notification) {
            $result['LastSchemeUpdate'] = grademonitor_helper::get_last_scheme_update($courseid);;
        }
        $result['ShowAverage'] = $record->show_others;

        return $result;
    }

    /**
     * Checks parameters.
     * @return \external_function_parameters
     */
    public static function grademonitor_get_table_parameters() {
        return new \external_function_parameters(
            [
                'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Checks return values.
     *
     * @return \external_single_structure
     */
    public static function grademonitor_get_table_returns() {
        return new \external_single_structure(
            [
                'Goals' => new \external_multiple_structure(
                    new \external_value(PARAM_INT, 'Goals of the users', VALUE_OPTIONAL)
                ),
                'Estimations' => new \external_multiple_structure(
                    new \external_value(PARAM_INT, 'Estimated grade for the users', VALUE_OPTIONAL)
                ),
                'Grades' => new \external_multiple_structure(
                    new \external_value(PARAM_INT, 'Grades for the users', VALUE_OPTIONAL)
                )
            ]
        );
    }

    /**
     * Gets the data for the teachers table view.
     *
     * @param int $contextid
     * @param int $courseid
     * @return array|array[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function grademonitor_get_table($contextid, $courseid) {
        global $USER, $DB;
        $params = self::validate_parameters(self::grademonitor_get_table_parameters(), [
            'contextid' => $contextid,
            'courseid'  => $courseid
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        if (render_view::is_student($context, $USER->id)) {
            return [
                'Goals' => [],
                'Estimations' => [],
                'Grades' => [],
            ];
        }

        $students = grademonitor_helper::get_course_students($context);
        $totalpoints = grademonitor_helper::get_course_max_points($courseid);

        // Goals section.
        $goals = [];
        $positive = 0;
        for ($i = 1; $i < 5; $i++) {
            $goal = grademonitor_helper::get_desired_goals($courseid, $i);
            $positive += $goal;
            $goals[] = $goal;
        }
        $goals[] = (count($students) - $positive);

        // Estimations section.
        $params['courseid'] = $courseid;

        $sql = "SELECT * FROM (SELECT *, ROW_NUMBER() OVER (PARTITION BY userid ORDER BY timecreated DESC) rn
                                FROM {lytix_grademonitor}) gm
                                WHERE gm.rn = 1 AND gm.courseid = :courseid";

        $records = $DB->get_records_sql($sql, $params);

        $tmp = [];
        $items = grademonitor_helper::get_course_items($courseid);
        foreach ($records as $record) {
            if (property_exists($record, 'estimations')) {
                $record = json_decode($record->estimations);
                $userestpoints = 0;
                foreach ($record->Estimations as $est) {
                    $userestpoints += $est->est;
                }
                $tmp[] = ($userestpoints / count($items) / $totalpoints) * 100;
            }
        }

        $estimations = grademonitor_helper::translate_percentages_to_grades($courseid, $tmp);

        // Grades section.
        $rawgrades = [];
        foreach ($students as $student) {
            $rawgrades[] = ((float)grade_get_course_grade($student->id, $courseid)->grade / $totalpoints) * 100;
        }

        $grades = grademonitor_helper::translate_percentages_to_grades($courseid, $rawgrades);

        return [
            'Goals' => $goals,
            'Estimations' => $estimations,
            'Grades' => $grades
        ];
    }


    /**
     * Checks parameters.
     * @return \external_function_parameters
     */
    public static function grademonitor_get_history_parameters() {
        return new \external_function_parameters(
            [
                'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Checks return values.
     *
     * @return \external_single_structure
     */
    public static function grademonitor_get_history_returns() {
        return new \external_single_structure(
            [
                'Start' => new \external_value(PARAM_INT, 'Start as UNIX timestamp', VALUE_REQUIRED),
                'Total' => new \external_value(PARAM_INT, 'Total number of students', VALUE_REQUIRED),
                'Count' => new \external_multiple_structure(
                    new \external_multiple_structure(
                        new \external_value(PARAM_INT, 'Goals of the users', VALUE_OPTIONAL)
                    ),
                    new \external_multiple_structure(
                        new \external_value(PARAM_INT, 'Estimated grade for the users', VALUE_OPTIONAL)
                    ),
                    new \external_multiple_structure(
                        new \external_value(PARAM_INT, 'Grades for the users', VALUE_OPTIONAL)
                    )
                ),
                'Percentage' => new \external_multiple_structure(
                    new \external_value(PARAM_FLOAT, 'Odd values are estimatins, even are grades', VALUE_OPTIONAL)
                )
            ]
        );
    }

    /**
     * Gets the data for the teacher's history view.
     *
     * @param int $contextid
     * @param int $courseid
     * @return array|array[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function grademonitor_get_history($contextid, $courseid) {
        global $USER, $DB;
        $params = self::validate_parameters(self::grademonitor_get_history_parameters(), [
            'contextid' => $contextid,
            'courseid' => $courseid
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $students = grademonitor_helper::get_course_students($context);

        $start = course_settings::getcoursestartdate($courseid);
        $end = course_settings::getcourseenddate($courseid);
        $tmp = course_settings::getcoursestartdate($courseid);
        $end = new \DateTime("now");
        $intervals = [];

        while ($tmp->getTimestamp() < $end->getTimestamp()) {
            $week = new \stdClass();
            $week->start = strtotime('first day of this week', $start->getTimestamp());
            $week->end = strtotime('last day of this week', $tmp->getTimestamp());
            $intervals[] = $week;

            $tmp->modify("+ 1 week");
        }

        $count = [];
        $percentage = [];

        foreach ($intervals as $interval) {

            $sql = 'SELECT DISTINCT userid, goal, estimations, timecreated FROM {lytix_grademonitor} gm
            WHERE timecreated = (SELECT MAX(gm2.timecreated) FROM {lytix_grademonitor} gm2
            WHERE gm2.userid= gm.userid AND gm2.courseid = :courseid AND gm2.timecreated > :intervalstart
            AND gm2.timecreated < :intervalend)';

            $params = [
                'courseid' => $courseid,
                'intervalstart' => $interval->start,
                'intervalend' => $interval->end
            ];

            $records = $DB->get_records_sql($sql, $params);

            $goals = array_fill(0, 4, 0);
            $grades = array_fill(0, 5, 0);
            $estimations = array_fill(0, 5, 0);
            $checked = [];

            foreach ($records as $record) {
                // Extract the estimated grade.
                ++$estimations[grademonitor_helper::extract_estimated_goal($courseid, $record) - 1];

                // Extract the desired grade.
                if ($record->goal !== null || $record->goal > 0   ) {
                    $goals[(int)$record->goal - 1] = $goals[(int)$record->goal - 1] + 1;
                }
                // Extract percentage of active evaluations.
                $checked[] = grademonitor_helper::extract_checked_estimations($record);
            }

            // Extract grades from api.
            $filters = array(
                'id' => $courseid,
                'userids' => optional_param('userids', '', PARAM_SEQUENCE),
                'itemid' => optional_param('itemid', 0, PARAM_INT),
                'grader' => optional_param('grader', 0, PARAM_INT),
                'datefrom' => optional_param('datefrom', $interval->start, PARAM_INT),
                'datetill' => optional_param('datetill', $interval->end, PARAM_INT),
                'revisedonly' => optional_param('revisedonly', 0, PARAM_INT),
            );
            $url = new \moodle_url('/grade/report/history/index.php', array('id' => $courseid, 'showreport' => 1));
            $gmhistory = new  grademonitor_history('gradereport_history', $context, $url, $filters);
            $totalgradings = $gmhistory->query_db(0);
            // Remove old gradings from last semester, just get gradings from students enrolled in the course.
            // Because of this, it is not possible to map the course for several semesters.
            $relevantgradings = array_intersect_key($totalgradings, $students);
            $graded = count($students) ? (100.0 / count($students) * count($relevantgradings)) : 0;

            $maxpoints = grademonitor_helper::get_course_max_points($courseid);
            foreach ($relevantgradings as $relevantgrade) {
                if ( $maxpoints > 0.0 && $relevantgrade->finalgrade > 0.0 &&
                    $relevantgrade->timemodified >= $start->getTimestamp() &&
                    $relevantgrade->timemodified <= $end->getTimestamp()) {

                    $percent = (float)$relevantgrade->finalgrade / $maxpoints;
                    $grade = grademonitor_helper::translate_percentage_to_grade($courseid, $percent);
                    $grades[$grade - 1] = $grades[$grade - 1] + 1;
                }
            }

            array_push($count, $grades, $estimations, $goals);

            // Add the Percentages for the second row.
            $percentage[] = $graded;
            $percentage[] = calculation_helper::median($checked) * 100;
        }

        return [
            'Start' => $start->getTimestamp(),
            'Total' => count($students),
            'Count' => array_reverse($count),
            'Percentage' => array_reverse($percentage)
        ];
    }
}
