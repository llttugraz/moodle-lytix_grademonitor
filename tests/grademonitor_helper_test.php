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
 * Choose and download exam backups
 *
 * @package    lytix_grademonitor
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace lytix_grademonitor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/lib/tests/grades_external_test.php');
require_once($CFG->dirroot . '/grade/querylib.php');

use core\grades_external_test;
use lytix_helper\dummy;


/**
 * Class grademonitor_history_test
 * @coversDefaultClass \lytix_grademonitor\grademonitor_helper
 */
class grademonitor_helper_test extends grades_external_test {
    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Variable for context.
     *
     * @var \context_course
     */
    private $context = null;

    /**
     * Variable for student2.
     *
     * @var \stdClass|null
     */
    private $student2 = null;

    /**
     * Test setter Method.
     * @covers ::generate_grademonitor_data_for_user
     * @covers ::set_grademonitor_info
     * @covers ::get_grademonitor_info
     * @return void
     * @throws \dml_exception
     */
    public function test_set_get_grademonitor_info() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $record = new \stdClass();
        $record->goal = 3;
        $record->scheme_update = 0;
        $record->estimations = '{"Estimations":[],"CheckedIndexes":[]}';
        $record->show_others = 1;
        $record->dismiss_notification = 0;
        $record->future = 'is now';
        $record = grademonitor_helper::set_grademonitor_info($this->student2->id, $this->course->id, $record);
        $record2 = grademonitor_helper::get_grademonitor_info($this->student2->id, $this->course->id);

        // Basic asserts.
        $this->basic_asserts($record);

        // Advanced asserts.
        $this->advanced_asserts($record);

        $this->assertEquals($record->userid, $record2->userid, "user id not matching.");
        $this->assertEquals($record->courseid, $record2->courseid, "Course id not matching.");
        $this->assertEquals($record->goal, $record2->goal, "goal not matching.");
        $this->assertEquals($record->scheme_update, $record2->scheme_update, "scheme_update not matching.");
        $this->assertEquals($record->estimations, $record2->estimations, "estimations not matching.");
        $this->assertEquals($record->show_others, $record2->show_others, "show_others not matching.");
        $this->assertEquals($record->dismiss_notification, $record2->dismiss_notification, "dismiss_notification not matching.");
        $this->assertEquals($record->future, $record2->future, "future not matching.");
        $this->assertEquals($record->timecreated, $record2->timecreated, "timecreated not matching.");
    }

    /**
     * Test alternative path of getter function.
     * @covers ::get_grademonitor_info
     * @return void
     * @throws \dml_exception
     */
    public function test_get_grademonitor_info_new() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $record = grademonitor_helper::get_grademonitor_info($this->student2->id, $this->course->id);

        // Basic Asserts.
        $this->basic_asserts($record);
        // Advanced asserts.
        $this->assertEquals($this->student2->id, $record->userid, "user id not matching.");
        $this->assertEquals($this->course->id, (int)$record->courseid, "Course id not matching.");
        $this->assertEquals(3, $record->goal, "goal not matching.");
        $this->assertEquals(0, $record->scheme_update, "scheme_update not matching.");
        $result = grademonitor_helper::get_course_items($this->course->id);
        $id1 = reset($result)->id;
        $id2 = end($result)->id;
        $this->assertEquals(
            '{"Estimations":[{"pos":0,"id":' . $id1 . ',"est":70},{"pos":1,"id":' . $id2
            . ',"est":70}],"CheckedIndexes":[{"pos":0,"id":' . $id1 . ',"checked":false},{"pos":1,"id":' . $id2
            . ',"checked":false}]}',
            $record->estimations, "estimations not matching.");
        $this->assertEquals(1, (int)$record->show_others, "show_others not matching.");
        $this->assertEquals(0, $record->dismiss_notification, "dismiss_notification not matching.");
        $this->assertEquals('', $record->future, "future not matching.");
        $this->assertTrue((new \DateTime())->getTimestamp() >= $record->timecreated, "timecreated not possible.");
    }

    /**
     * Test get_course_items.
     * @covers ::get_course_items
     * @return void
     * @throws \dml_exception
     */
    public function test_get_course_items() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_course_items($this->course->id);
        // Basic Assert.
        $this->assertEquals(2, count($result), "There must be 2 course items.");
        // Advanced Assert.
        $first = reset($result);
        $last = end($result);
        $this->assertEquals('The assignment', $first->itemname, "Wrong itemname.");
        $this->assertEquals('Team work', $last->itemname, "Wrong itemname.");
        $this->assertEquals('100.00000', $first->grademax, "Grademax not matching.");
        $this->assertEquals('5.00000', $last->grademax, "Grademax not matching.");
    }

    /**
     * Test get_course_grades.
     * @covers ::get_course_grades
     * @return void
     * @throws \dml_exception
     */
    public function test_get_course_grades() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_course_grades($this->course->id);
        // Basic Assert.
        $this->assertEquals(2, count($result), "There must be 2 grade items.");
        // Advanced Assert.
        $first = reset($result);
        $last = end($result);
        $this->assertObjectHasAttribute('id', $first, "id attribute missing.");
        $this->assertObjectHasAttribute('itemid', $first, "itemid attribute missing.");
        $this->assertObjectHasAttribute('userid', $first, "userid attribute missing.");
        $this->assertObjectHasAttribute(
            'rawgrademax', $first, "rawgrademax attribute missing.");
        $this->assertObjectHasAttribute(
            'finalgrade', $first, "finalgrade attribute missing.");
        $this->assertObjectHasAttribute(
            'aggregationstatus', $first, "aggregationstatus attribute missing.");
    }

    /**
     * Test get_course_scheme.
     * @covers ::get_course_scheme
     * @return void
     * @throws \dml_exception
     */
    public function test_get_course_scheme() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_course_scheme($this->course->id);
        $this->assertEquals(4, count($result), "There must be 2 course items.");
        // Advanced Assert.
        $this->assertEquals(50.0, $result[0], "Wrong Percentage for 4.");
        $this->assertEquals(70.0, $result[1], "Wrong Percentage for 3.");
        $this->assertEquals(80.0, $result[2], "Wrong Percentage for 2.");
        $this->assertEquals(90.0, $result[3], "Wrong Percentage for 1.");

        $result2 = grademonitor_helper::get_course_scheme($this->course->id);
        $this->assertEquals($result, $result2);
    }

    /**
     * Test get_last_scheme_update.
     * @covers ::get_last_scheme_update
     * @return void
     * @throws \dml_exception
     */
    public function test_get_last_scheme_update() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_last_scheme_update($this->course->id);
        $this->assertIsInt($result, "Value should be a timestamp.");
        $this->assertTrue(0 < $result && $result <= (new \DateTime())->getTimestamp(), "Timestamp not possible.");
    }

    /**
     * Test get_desired_goals.
     * @covers ::get_desired_goals
     * @return void
     * @throws \dml_exception
     */
    public function test_get_desired_goals() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_desired_goals($this->course->id, 1);
        $this->assertEquals(0, $result, "No desired 1 from student");
        $result = grademonitor_helper::get_desired_goals($this->course->id, 2);
        $this->assertEquals(0, $result, "One desired 2 from student");
        $result = grademonitor_helper::get_desired_goals($this->course->id, 3);
        $this->assertEquals(1, $result, "No desired 3 from student");
        $result = grademonitor_helper::get_desired_goals($this->course->id, 4);
        $this->assertEquals(0, $result, "No desired 4 from student");
    }

    /**
     * Test translate_percentages_to_grades.
     * @covers ::translate_percentages_to_grades
     * @return void
     * @throws \dml_exception
     */
    public function test_translate_percentages_to_grades() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::translate_percentages_to_grades($this->course->id, [50.0, 60.0]);
        $this->assertEquals(2, $result[3], "There shold be two 4s");
        $result = grademonitor_helper::translate_percentages_to_grades($this->course->id, [70.0, 75.0]);
        $this->assertEquals(2, $result[2], "There shold be two 3s");
        $result = grademonitor_helper::translate_percentages_to_grades($this->course->id, [80.0, 85.0]);
        $this->assertEquals(2, $result[1], "There shold be two 2s");
        $result = grademonitor_helper::translate_percentages_to_grades($this->course->id, [90.0, 100.0]);
        $this->assertEquals(2, $result[0], "There shold be two 1s");
    }

    /**
     * Test translate_percentage_to_grades.
     * @covers ::translate_percentage_to_grade
     * @return void
     * @throws \dml_exception
     */
    public function test_translate_percentage_to_grades() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        // Assert a 5.
        $percentage = 40.0;
        $result = grademonitor_helper::translate_percentage_to_grade($this->course->id, $percentage);
        $this->assertEquals(5, $result, '40% should be 5.');
        // Assert a 4.
        $percentage = 51.0;
        $result = grademonitor_helper::translate_percentage_to_grade($this->course->id, $percentage);
        $this->assertEquals(4, $result, '51% should be 4.');
        // Assert a 3.
        $percentage = 70.0;
        $result = grademonitor_helper::translate_percentage_to_grade($this->course->id, $percentage);
        $this->assertEquals(3, $result, '70% should be 3.');
        // Assert a 2.
        $percentage = 80.0;
        $result = grademonitor_helper::translate_percentage_to_grade($this->course->id, $percentage);
        $this->assertEquals(2, $result, '80% should be 2.');
        // Assert a 1.
        $percentage = 90.0;
        $result = grademonitor_helper::translate_percentage_to_grade($this->course->id, $percentage);
        $this->assertEquals(1, $result, '90% should be 1.');
    }

    /**
     * Test get_course_students.
     * @covers ::get_course_students
     * @return void
     * @throws \dml_exception
     */
    public function test_get_course_students() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_course_students($this->context);
        $this->assertEquals(2, count($result), "There should be two students enrolled.");
    }

    /**
     * Test get_course_max_points.
     * @covers ::get_course_max_points
     * @return void
     * @throws \dml_exception
     */
    public function test_get_course_max_points() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $result = grademonitor_helper::get_course_max_points($this->course->id);
        $this->assertEquals('100.00000', $result, "Maxpoints should be 100");
    }

    /**
     * Test extract_estimated_goal.
     * @covers ::extract_estimated_goal
     * @return void
     * @throws \dml_exception
     */
    public function test_extract_estimated_goal() {
        // Setup.
        global $DB;
        $this->resetAfterTest();
        $this->set_variables();

        $sql = 'SELECT DISTINCT userid, goal, estimations, timecreated
                FROM {lytix_grademonitor} WHERE courseid = :courseid';
        $params = [
            'courseid' => $this->course->id
        ];

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $result = grademonitor_helper::extract_estimated_goal($this->course->id, $record);
            $this->assertEquals(5, $result, 'The estimations should be a 5.');
        }
    }

    /**
     * Test extract_checked_estimations.
     * @covers ::extract_checked_estimations
     * @return void
     * @throws \dml_exception
     */
    public function test_extract_checked_estimations() {
        // Setup.
        global $DB;
        $this->resetAfterTest();
        $this->set_variables();

        $record = $DB->get_record('lytix_grademonitor', ['courseid' => $this->course->id]);
        $result = grademonitor_helper::extract_checked_estimations($record);
        $this->assertEquals(0, $result, 'There should no checked estimations in table.');
    }

    /**
     * Helper to set all relevant variables.
     * @throws \dml_exception
     */
    public function set_variables(): void {
        $assignmentname = 'The assignment';
        $student1rawgrade = 10;
        $student2rawgrade = 20;
        list($course, $assignment, $student1, $student2, $teacher, $parent) =
            $this->load_test_data($assignmentname, $student1rawgrade, $student2rawgrade);

        $this->course = $course;
        $this->context = \context_course::instance($this->course->id);
        $this->student1 = $student1;
        $this->student2 = $student2;

        // Generate grademonitor records.
        grademonitor_helper::generate_grademonitor_data_for_user($this->course, $student1);
        // Add course to config list.
        set_config('course_list', $this->course->id, 'local_lytix');
        // Set platform.
        set_config('platform', 'learners_corner', 'local_lytix');
        // Set course start and enddate.
        $fivemonthsago = new \DateTime('5 months ago');
        $fivemonthsago->setTime(0, 0);
        set_config('semester_start', $fivemonthsago->format('Y-m-d'), 'local_lytix');
        $today = new \DateTime('today midnight');
        date_add($today, date_interval_create_from_date_string('1 day'));
        set_config('semester_end', $today->format('Y-m-d'), 'local_lytix');
    }

    /**
     * Helper function to test the grademonitor record.
     * @param \stdClass $record
     * @return void
     */
    public function basic_asserts(\stdClass $record): void {
        $this->assertIsObject($record, "Return value is not an object.");
        $this->assertObjectHasAttribute('userid', $record, "userid missing as key.");
        $this->assertObjectHasAttribute('courseid', $record, "courseid missing as key.");
        $this->assertObjectHasAttribute('goal', $record, "goal missing as key.");
        $this->assertObjectHasAttribute('scheme_update', $record, "scheme_update missing as key.");
        $this->assertObjectHasAttribute('estimations', $record, "estimations missing as key.");
        $this->assertObjectHasAttribute('show_others', $record, "show_others missing as key.");
        $this->assertObjectHasAttribute('dismiss_notification', $record, "dismiss_notification missing as key.");
        $this->assertObjectHasAttribute('future', $record, "future missing as key.");
        $this->assertObjectHasAttribute('timecreated', $record, "timecreated missing as key.");
    }

    /**
     * Helper function to test the grademonitor record.
     * @param \stdClass $record
     * @return void
     */
    public function advanced_asserts(\stdClass $record): void {
        $this->assertEquals($this->student2->id, $record->userid, "user id not matching.");
        $this->assertEquals($this->course->id, $record->courseid, "Course id not matching.");
        $this->assertEquals(3, $record->goal, "goal not matching.");
        $this->assertEquals(0, $record->scheme_update, "scheme_update not matching.");
        $this->assertEquals('{"Estimations":[],"CheckedIndexes":[]}', $record->estimations, "estimations not matching.");
        $this->assertEquals(1, $record->show_others, "show_others not matching.");
        $this->assertEquals(0, $record->dismiss_notification, "dismiss_notification not matching.");
        $this->assertEquals('is now', $record->future, "future not matching.");
        $this->assertTrue((new \DateTime())->getTimestamp() >= $record->timecreated, "timecreated not possible.");
    }
}
