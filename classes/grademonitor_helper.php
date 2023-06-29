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

use lytix_config\render_view;
use context_course;

/**
 * Class grademonitor_helper
 */
class grademonitor_helper {
    /**
     * Stores the new values into the database.
     *
     * @param int $userid
     * @param int $courseid
     * @param \stdClass $info
     * @return \stdClass
     * @throws \dml_exception
     */
    public static function set_grademonitor_info($userid, $courseid, $info) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->goal = $info->goal;
        $record->scheme_update = (int)$info->scheme_update ?: 0;
        $record->estimations = is_string($info->estimations) ? $info->estimations :
            (json_encode($info->estimations) ?: '{"Estimations":[],"CheckedIndexes":[]}');
        $record->show_others = (int)$info->show_others ?: 0;
        $record->dismiss_notification = (int)$info->dismiss_notification ?: 0;
        $record->future = ($info->future) ?: '';
        $record->timecreated = (new \DateTime())->getTimestamp();

        $record->id = $DB->insert_record('lytix_grademonitor', $record);
        return $record;
    }

    /**
     * Get the needed information for the front end.
     *
     * @param int $userid
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function get_grademonitor_info($userid, $courseid) {
        global $DB;

        $params['userid'] = $userid;
        $params['courseid'] = $courseid;

        $sql = "SELECT * FROM {lytix_grademonitor} WHERE userid = :userid
                                     AND courseid = :courseid ORDER BY timecreated DESC LIMIT 1";

        $record = $DB->get_record_sql($sql, $params);
        if (!$record) {
            $record = new \stdClass();

            $items = self::get_course_items($courseid);
            $pos = 0;
            $estimations = [];
            $checkedidx = [];
            foreach ($items as $item) {
                $dirty = new \stdClass();
                $dirty->pos = $pos;
                $dirty->id = (int)$item->id;
                $dirty->est = 70;
                $estimations[] = $dirty;

                $dirty = new \stdClass();
                $dirty->pos = $pos;
                $dirty->id = (int)$item->id;
                $dirty->checked = false;
                $checkedidx[] = $dirty;

                $pos++;
            }

            $final = [
                'Estimations' => $estimations,
                'CheckedIndexes' => $checkedidx
            ];

            $record->goal = 3;
            $record->scheme_update = 0;
            $record->estimations = json_encode($final);
            $record->show_others = 1;
            $record->dismiss_notification = 0;
            $record->future = '';
            $record = self::set_grademonitor_info($userid, $courseid, $record);
        }
        return $record;
    }

    /**
     * Get the course items.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_course_items($courseid) {
        global $DB;

        $params['courseid'] = $courseid;

        $sql = "SELECT id, itemname, itemtype, grademax
        FROM {grade_items} WHERE courseid = :courseid AND itemtype != 'course' AND itemtype != 'category'
        ORDER BY timecreated";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the grades from the course.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_course_grades($courseid) {
        global $DB;

        $params['courseid'] = $courseid;

        $sql = "SELECT id, itemid, userid, rawgrademax, finalgrade, aggregationstatus
        FROM {grade_grades} WHERE userid IN
                                      (SELECT userid FROM {grade_grades} gg
                                      INNER JOIN {grade_items} gi WHERE gg.itemid = gi.id
                                      AND gi.courseid = :courseid )
                                        AND aggregationstatus != 'unknown' AND aggregationstatus != 'novalue'
                                         ORDER BY itemid";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get the course scheme.
     *
     * @param int $contextid
     * @return array|float[]
     * @throws \dml_exception
     */
    public static function get_course_scheme($contextid) {
        global $DB;

        $scheme = [];
        if ($DB->record_exists('grade_letters', ['contextid' => $contextid])) {
            $letters = $DB->get_records('grade_letters', ['contextid' => $contextid],
                'lowerboundary ASC', 'lowerboundary', 1, 4);
            foreach ($letters as $letter) {
                $scheme[] = (float)$letter->lowerboundary;
            }
        } else {
            $scheme = [50.0, 70.0, 80.0, 90.0];
        }
        return $scheme;
    }

    /**
     * Get time of last grading scheme update.
     *
     * @param int $courseid
     * @return int
     * @throws \dml_exception
     */
    public static function get_last_scheme_update($courseid) {
        global $DB;

        if ($DB->record_exists('grade_categories', ['courseid' => $courseid])) {
            $record = $DB->get_records('grade_categories', ['courseid' => $courseid],
                'timemodified DESC', 'timemodified', 0, 1);
            return (int)(reset($record))->timemodified;
        }
        return 0;
    }

    /**
     * Get the desired goals from the students.
     *
     * @param int $courseid
     * @param int $goal
     * @return int|void
     * @throws \dml_exception
     */
    public static function get_desired_goals($courseid, $goal) {
        global $DB;
        $params['courseid'] = $courseid;
        $params['goal'] = $goal;

        $sql = "SELECT * FROM (SELECT *, ROW_NUMBER() OVER (PARTITION BY userid ORDER BY timecreated DESC) rn
                                FROM {lytix_grademonitor}) gm
                                WHERE gm.rn = 1 AND gm.courseid = :courseid AND gm.goal = :goal";

        $records = $DB->get_records_sql($sql, $params);
        return count($records);
    }

    /**
     * Get the grade from the percentage achieved, corresponding to the course scheme.
     *
     * @param int $courseid
     * @param array $percentages
     * @return array
     * @throws \dml_exception
     */
    public static function translate_percentages_to_grades($courseid, $percentages) {

        $context = context_course::instance($courseid);
        $scheme = self::get_course_scheme($context->id);

        $grades = array_fill(0, 5, 0);

        foreach ($percentages as $percentage) {
            if ($percentage <= $scheme[0]) {
                $grades[4]++;
            }
            if ($percentage >= $scheme[0] && $percentage < $scheme[1]) {
                $grades[3]++;
            }
            if ($percentage >= $scheme[1] && $percentage < $scheme[2]) {
                $grades[2]++;
            }
            if ($percentage >= $scheme[2] && $percentage < $scheme[3]) {
                $grades[1]++;
            }
            if ($percentage >= $scheme[3]) {
                $grades[0]++;
            }
        }

        $students = self::get_course_students($context);
        // Inactive students count as negative.
        $grades[4] += (count($students) - ($grades[4] + $grades[3] + $grades[2] + $grades[1] + $grades[0]));

        return $grades;
    }

    /**
     * Translates the percent to a grade.
     *
     * @param int $courseid
     * @param float $percentage
     * @return int
     * @throws \dml_exception
     */
    public static function translate_percentage_to_grade($courseid, $percentage) {

        $context = context_course::instance($courseid);
        $scheme = self::get_course_scheme($context->id);

        if ($percentage <= $scheme[0]) {
            return 5;
        }
        if ($percentage < $scheme[1]) {
            return 4;
        }
        if ($percentage < $scheme[2]) {
            return 3;
        }
        if ($percentage < $scheme[3]) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * Get all students in this course.
     *
     * @param \context $context
     * @return array
     */
    public static function get_course_students($context) {
        $users = get_enrolled_users($context);
        $students = [];
        foreach ($users as $user) {
            if (render_view::is_student($context, $user->id)) {
                $students[$user->id] = $user;
            }
        }
        return $students;
    }

    /**
     * Get the maximum points achievable int this course.
     *
     * @param int $courseid
     * @return int
     * @throws \dml_exception
     */
    public static function get_course_max_points($courseid) {
        $coursegrades = grade_get_course_grades($courseid);
        return $coursegrades->grademax;
    }

    /**
     * Extracts the estimation and retuns as goal.
     * @param int $courseid
     * @param array|\stdClass $record
     * @return int
     * @throws \dml_exception
     */
    public static function extract_estimated_goal($courseid, $record) {

        $raw = json_decode($record->estimations);
        $cnt = 0;
        $sum = 0;
        foreach ($raw->Estimations as $estimation) {
            $sum += $estimation->est;
            $cnt++;
        }
        $percent = ($cnt) > 0 ? ($sum / $cnt) : 0;
        return self::translate_percentage_to_grade($courseid, $percent);
    }

    /**
     * Extracts the amount of checked estimations per user as a percentage over all possible items.
     * @param array|\stdClass $record
     * @return float|int
     */
    public static function extract_checked_estimations($record) {

        $raw = json_decode($record->estimations);
        $cnt = 0;
        $all = 0;
        foreach ($raw->CheckedIndexes as $estimation) {
            if ($estimation->checked) {
                $cnt++;
            }
            $all++;
        }
        return ($cnt) > 0 ? ($cnt / $all) : 0;
    }

    /**
     * Helper to fill the lytix_grademonitor table.
     * @param \stdClass $course
     * @param \stdClass $user
     * @return void
     * @throws \dml_exception
     */
    public static function generate_grademonitor_data_for_user($course, $user) {
        global $DB;
        $record = new \stdClass();
        $record->courseid = $course->id;
        $record->userid = $user->id;
        $record->goal = 3;
        $record->scheme_update = 0;
        $record->estimations = '{"Estimations":[],"CheckedIndexes":[]}';
        $record->show_others = rand(0, 1);
        $record->dismiss_notification = 0;
        $record->timecreated = (new \DateTime())->getTimestamp();
        $record->future = "";
        $DB->insert_record('lytix_grademonitor', $record);
    }
}
