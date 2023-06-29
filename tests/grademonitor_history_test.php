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

use core\grades_external_test;
use lytix_helper\dummy;

/**
 * Class grademonitor_history_test
 * @coversDefaultClass \lytix_grademonitor\grademonitor_history
 */
class grademonitor_history_test extends grades_external_test {
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
     * Test query_db.
     *
     * @covers ::__construct
     * @covers ::query_db
     */
    public function test_query_db() {
        // Setup.
        $this->resetAfterTest();
        $this->set_variables();

        $start = (new \DateTime(get_config('local_lytix', 'semester_start')))->getTimestamp();
        $end = (new \DateTime(get_config('local_lytix', 'semester_end')))->getTimestamp();

        $filters = array(
            'id' => $this->course->id,
            'userids' => optional_param('userids', '', PARAM_SEQUENCE),
            'itemid' => optional_param('itemid', 0, PARAM_INT),
            'grader' => optional_param('grader', 0, PARAM_INT),
            'datefrom' => optional_param('datefrom', $start, PARAM_INT),
            'datetill' => optional_param('datetill', $end, PARAM_INT),
            'revisedonly' => optional_param('revisedonly', 0, PARAM_INT),
        );
        $url = new \moodle_url('/grade/report/history/index.php', array('id' => $this->course->id, 'showreport' => 1));
        $gmhistory = new  grademonitor_history('gradereport_history', $this->context, $url, $filters);
        $results = $gmhistory->query_db(0);
        $this->assertNotEmpty($results, "Result is empty!");
        $this->assertIsArray($results, "Result should be an array");
        $this->assertEquals(2, count($results), "There should be ecatly 2 items.");
        foreach ($results as $item) {
            $this->assertObjectHasAttribute('id', $item, "id missing as key.");
            $this->assertObjectHasAttribute('timemodified', $item, "timemodified missing as key.");
            $this->assertObjectHasAttribute('itemid', $item, "itemid missing as key.");
            $this->assertObjectHasAttribute('userid', $item, "userid missing as key.");
            $this->assertObjectHasAttribute('finalgrade', $item, "finalgrade missing as key.");
            $this->assertObjectHasAttribute('usermodified', $item, "usermodified missing as key.");
            $this->assertObjectHasAttribute('source', $item, "source missing as key.");
            $this->assertObjectHasAttribute('overridden', $item, "overridden missing as key.");
            $this->assertObjectHasAttribute('locked', $item, "locked missing as key.");
            $this->assertObjectHasAttribute('excluded', $item, "excluded missing as key.");
            $this->assertObjectHasAttribute('feedback', $item, "feedback missing as key.");
            $this->assertObjectHasAttribute('feedbackformat', $item, "feedbackformat missing as key.");
            $this->assertObjectHasAttribute('itemtype', $item, "itemtype missing as key.");
            $this->assertObjectHasAttribute('itemmodule', $item, "itemmodule missing as key.");
            $this->assertObjectHasAttribute('iteminstance', $item, "iteminstance missing as key.");
            $this->assertObjectHasAttribute('itemnumber', $item, "itemnumber missing as key.");
            $this->assertObjectHasAttribute('firstnamephonetic', $item, "firstnamephonetic missing as key.");
            $this->assertObjectHasAttribute('lastnamephonetic', $item, "lastnamephonetic missing as key.");
            $this->assertObjectHasAttribute('middlename', $item, "middlename missing as key.");
            $this->assertObjectHasAttribute('alternatename', $item, "alternatename missing as key.");
            $this->assertObjectHasAttribute('firstname', $item, "firstname missing as key.");
            $this->assertObjectHasAttribute('lastname', $item, "lastname missing as key.");
            $this->assertObjectHasAttribute('graderfirstnamephonetic', $item, "graderfirstnamephonetic missing as key.");
            $this->assertObjectHasAttribute('graderlastnamephonetic', $item, "graderlastnamephonetic missing as key.");
            $this->assertObjectHasAttribute('gradermiddlename', $item, "gradermiddlename missing as key.");
            $this->assertObjectHasAttribute('graderalternatename', $item, "graderalternatename missing as key.");
            $this->assertObjectHasAttribute('graderfirstname', $item, "graderfirstname missing as key.");
            $this->assertObjectHasAttribute('graderlastname', $item, "graderlastname missing as key.");
            $this->assertObjectHasAttribute('prevgrade', $item, "prevgrade missing as key.");
            $this->assertObjectHasAttribute('itemname', $item, "itemname missing as key.");
        }
    }

    /**
     * Helper function to set all relevant variables.
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
        $this->context = \context_course::instance($this->course->id);

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
