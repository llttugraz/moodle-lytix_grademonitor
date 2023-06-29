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
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace lytix_grademonitor;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/lib/tests/grades_external_test.php');

use coding_exception;
use core\grades_external_test;
use external_api;

/**
 * Class privacy_lib_test
 * @coversDefaultClass \lytix_grademonitor\grademonitor_lib
 */
class grademonitor_lib_test extends grades_external_test {

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
     * Variable for student1.
     *
     * @var \stdClass|null
     */
    private $student1 = null;

    /**
     * Test grademonitor_get.
     *
     * @covers ::grademonitor_get
     * @covers ::grademonitor_get_returns
     * @covers ::grademonitor_get_parameters
     *
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws \invalid_response_exception
     * @throws \invalid_parameter_exception
     */
    public function test_grademonitor_get() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();
        $this->setUser($this->student1);

        // Call the service.
        $result = grademonitor_lib::grademonitor_get($this->context->id, $this->course->id);
        // Basic asserts.
        $this->assertIsArray($result, "Return is not an array.");
        $this::assertEquals(5, count($result), "Not all keys are present.");
        $this->assertTrue(key_exists('Goal', $result), "Goal missing as key.");
        $this->assertTrue(key_exists('Items', $result), "Items missing as key.");
        $this->assertTrue(key_exists('Scheme', $result), "Scheme missing as key.");
        $this->assertTrue(key_exists('ShowAverage', $result), "ShowAverage missing as key.");
        $this->assertTrue(key_exists('LastSchemeUpdate', $result), "LastSchemeUpdate missing as key.");
        // Advanced asserts.
        $this->assertEquals(8, count($result['Items']), "Wrong Item count.");
        $this->assertTrue(key_exists('IDs', $result['Items']), "Itemkey IDs missing.");
        $this->assertTrue(key_exists('Names', $result['Items']), "Itemkey Names missing.");
        $this->assertTrue(key_exists('MaxScores', $result['Items']), "Itemkey MaxScores missing.");
        $this->assertTrue(key_exists('Scores', $result['Items']), "Itemkey Scores missing.");
        $this->assertTrue(key_exists('ClassAvgs', $result['Items']), "Itemkey ClassAvgs missing.");
        $this->assertTrue(key_exists('Estimations', $result['Items']), "Itemkey Estimations missing.");
        $this->assertTrue(key_exists('OptionalIndexes', $result['Items']), "Itemkey OptionalIndexes missing.");
        $this->assertTrue(key_exists('CheckedIndexes', $result['Items']), "Itemkey CheckedIndexes missing.");
        $this->assertEquals(2, count($result['Items']['IDs']), "Wrong Item count, there should be exactly two.");

        // Check return values.
        external_api::clean_returnvalue(grademonitor_lib::grademonitor_get_returns(), $result);
    }

    /**
     * Test grademonitor_get as a role that is not student.
     *
     * @covers ::grademonitor_get
     * @covers ::grademonitor_get_returns
     * @covers ::grademonitor_get_parameters
     *
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws \invalid_response_exception
     * @throws \invalid_parameter_exception
     */
    public function test_grademonitor_get_no_student() {
        // Setup.
        $this->resetAfterTest(true);
        $this->set_variables();

        // Call the service.
        $result = grademonitor_lib::grademonitor_get($this->context->id, $this->course->id);
        // Basic asserts.
        $this->assertIsArray($result, "Return is not an array.");
        $this::assertEquals(5, count($result), "Not all keys are present.");
        $this->assertTrue(key_exists('Goal', $result), "Goal missing as key.");
        $this->assertTrue(key_exists('Items', $result), "Items missing as key.");
        $this->assertTrue(key_exists('Scheme', $result), "Scheme missing as key.");
        $this->assertTrue(key_exists('ShowAverage', $result), "ShowAverage missing as key.");
        $this->assertTrue(key_exists('LastSchemeUpdate', $result), "LastSchemeUpdate missing as key.");
        // Advanced asserts.
        $this->assertEquals(0, $result['Goal'], "Goal should be empty.");
        $this->assertEquals(8, count($result['Items']), "Wrong Item count.");
        $this->assertEquals([], $result['Scheme'], "Scheme should be empty.");
        $this->assertEquals(0, $result['ShowAverage'], "ShowAverage should be empty.");
        $this->assertEquals(false, $result['LastSchemeUpdate'], "LastSchemeUpdate should be empty.");

        // Check return values.
        external_api::clean_returnvalue(grademonitor_lib::grademonitor_get_returns(), $result);
    }

    /**
     * Test grademonitor_get_table.
     *
     * @covers ::grademonitor_get_table
     * @covers ::grademonitor_get_table_returns
     * @covers ::grademonitor_get_table_parameters
     *
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public function test_grademonitor_get_table() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        // Call the service.
        $result = grademonitor_lib::grademonitor_get_table($this->context->id, $this->course->id);

        // Basic asserts.
        $this->assertIsArray($result, "Return is not an array.");
        $this::assertEquals(3, count($result), "Not all keys are present.");
        $this->assertArrayHasKey('Goals', $result, "Goals key missing.");
        $this->assertArrayHasKey('Grades', $result, "Grades key missing.");
        $this->assertArrayHasKey('Estimations', $result, "Estimations key missing.");
        // Advanced asserts.
        $this->assertEquals(5, count($result['Goals']), "Wrong item count.");
        $this->assertEquals(5, count($result['Grades']), "Wrong item count.");
        $this->assertEquals(5, count($result['Estimations']), "Wrong item count.");
        // Check return values.
        external_api::clean_returnvalue(grademonitor_lib::grademonitor_get_table_returns(), $result);
    }

    /**
     * Test grademonitor_get_table as a student.
     *
     * @covers ::grademonitor_get_table
     * @covers ::grademonitor_get_table_returns
     * @covers ::grademonitor_get_table_parameters
     *
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public function test_grademonitor_get_table_as_student() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();
        $this->setUser($this->student1);

        // Call the service.
        $result = grademonitor_lib::grademonitor_get_table($this->context->id, $this->course->id);

        // Basic asserts.
        $this->assertIsArray($result, "Return is not an array.");
        $this::assertEquals(3, count($result), "Not all keys are present.");
        $this->assertArrayHasKey('Goals', $result, "Goals key missing.");
        $this->assertArrayHasKey('Grades', $result, "Grades key missing.");
        $this->assertArrayHasKey('Estimations', $result, "Estimations key missing.");
        // Advanced asserts.
        $this::assertEquals(0, count($result['Goals']), "Goals should be empty.");
        $this::assertEquals(0, count($result['Grades']), "Grades should be empty.");
        $this::assertEquals(0, count($result['Estimations']), "Estimations should be empty.");

        // Check return values.
        external_api::clean_returnvalue(grademonitor_lib::grademonitor_get_table_returns(), $result);
    }

    /**
     * Test grademonitor_get_history.
     *
     * @covers ::grademonitor_get_history
     * @covers ::grademonitor_get_history_returns
     * @covers ::grademonitor_get_history_parameters
     *
     * @throws \restricted_context_exception
     * @throws coding_exception
     * @throws \invalid_response_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public function test_grademonitor_get_history() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        // Call the service.
        $result = grademonitor_lib::grademonitor_get_history($this->context->id, $this->course->id);

        // Basic asserts.
        $this->assertIsArray($result, "Return is not an array.");
        $this::assertEquals(4, count($result), "Not all keys are present.");
        $this->assertArrayHasKey('Start', $result, "Start key missing.");
        $this->assertArrayHasKey('Total', $result, "Total key missing.");
        $this->assertArrayHasKey('Count', $result, "Count key missing.");
        $this->assertArrayHasKey('Percentage', $result, "Percantage  key missing.");
        // Advanced asserts.
        $this->assertIsInt($result['Start'], "Start has incorrect type.");
        $this->assertIsInt($result['Total'], "Total has incorrect type.");
        $this->assertIsArray($result['Count'], "Count has incorrect type.");
        $this->assertIsArray($result['Percentage'], "Percentage has incorrect type.");
        // Check return values.
        external_api::clean_returnvalue(grademonitor_lib::grademonitor_get_history_returns(), $result);
    }

    /**
     * Helper function.
     *
     * @return void
     * @throws \dml_exception
     */
    public function set_variables(): void {
        $assignmentname = 'The assignment';
        $student1rawgrade = 10;
        $student2rawgrade = 20;
        list($course, $assignment, $student1, $student2, $teacher, $parent) =
            $this->load_test_data($assignmentname, $student1rawgrade, $student2rawgrade);

        $this->course = $course;
        $this->assignment = $assignment;
        $this->context = \context_course::instance($this->course->id);
        $this->student1 = $student1;
        $this->student2 = $student2;
        $this->teacher = $teacher;
        // Generate grademonitor records.
        grademonitor_helper::generate_grademonitor_data_for_user($this->course, $student1);
        grademonitor_helper::generate_grademonitor_data_for_user($this->course, $student2);
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
}
