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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use coding_exception;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;
use dml_exception;

/**
 * Class privacy_lib_test.
 *
 * @coversDefaultClass \lytix_grademonitor\privacy\provider
 */
class privacy_lib_test extends provider_testcase {

    /**
     * Basic setup for these tests.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Helper to fill the table.
     * @param \stdClass $course
     * @param \stdClass $user
     * @return void
     * @throws \dml_exception
     */
    public static function generate_data_for_user($course, $user) {
        global $DB;
        $record = new \stdClass();
        $record->courseid = $course->id;
        $record->userid = $user->id;
        $record->goal = rand(1, 5);
        $record->scheme_update = rand(0, 1);
        $record->estimations = "title";
        $record->show_others = rand(0, 1);
        $record->dismiss_notification = 0;
        $record->timecreated = rand(1, 1000);
        $record->future = "future";
        $DB->insert_record('lytix_grademonitor', $record);
    }

    /**
     * Test getting the context for the user ID related to this plugin.
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        $user = $this->getDataGenerator()->create_user();
        $contextlist = privacy\provider::get_contexts_for_userid($user->id);
        $this->assertEmpty($contextlist);
    }

    /**
     * Test provider get_users_in_context method for a non-user context
     * @covers ::get_users_in_context
     * @return void
     */
    public function test_get_users_in_context_non_user_context() {
        $context = \context_system::instance();

        $userlist = new userlist($context, 'lyitx_diary');
        privacy\provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist);
    }

    /**
     * Test user has context, is in context and data got deleted
     * @covers ::get_contexts_for_userid
     * @covers ::get_users_in_context
     * @covers ::delete_data_for_users
     * @return void
     * @throws \dml_exception
     **/
    public function test_delete_user_data() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->generate_data_for_user($course, $user);
        $context = \context_course::instance($course->id);

        $this->assertTrue($this->privacy_get_context_sub_test($user, $context), 'User has no context');
        $this->assertTrue($this->privacy_get_users_sub_test($user), 'User not in context');
        $this->assertTrue($this->privacy_delte_users_sub_test($user), 'User data not deleted');
    }

    /**
     * Delete userdata over a contextlist
     * @covers ::delete_data_for_user
     * @return void
     * @throws \dml_exception
     */
    public function test_delte_user_over_contextlist() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->generate_data_for_user($course, $user);

        $this->assertTrue($this->privacy_delete_context_user_sub_test($user));
    }

    /**
     * Export userdata over a contextlist
     * @covers ::export_user_data
     * @return void
     * @throws \dml_exception
     */
    public function test_export_user_over_contextlist() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->generate_data_for_user($course, $user);

        $this->privacy_export_user_data_sub_test($user);
        $this->assertTrue(true);
    }

    /**
     * Erase all data
     * @covers ::delete_data_for_all_users_in_context
     * @return void
     * @throws \dml_exception
     */
    public function test_erase_users_data() {
        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->generate_data_for_user($course, $user1);
        $this->generate_data_for_user($course, $user2);
        $this->generate_data_for_user($course, $user3);

        $this->assertTrue($this->privacy_delete_context_sub_test());
    }

    /**
     * Test the metadata
     * @covers ::get_metadata
     * @return void
     * @throws \dml_exception
     */
    public function test_metadata() {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->generate_data_for_user($course, $user);

        $this->assertTrue($this->privacy_get_metadata_sub_test());
    }

    /**
     * Subroutine -> Test if the privacy delete_data_for_users of the provider works
     *
     * @param \stdClass $user the user to get deleted afterwards
     * @return bool if empty, all userdata was deleted
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function privacy_delte_users_sub_test(\stdClass $user) : bool {
        global $DB;
        $privacy = new privacy\provider();
        $context = new contextlist();
        $context->add_system_context();
        $ul = new approved_userlist(\context_system::instance(), "lytix/grademonitor", [$user->id]);
        $privacy::delete_data_for_users($ul);
        return empty($DB->get_records_sql(
            "SELECT * FROM {lytix_grademonitor} WHERE userid = :userid", [ "userid" => $user->id]));
    }

    /**
     * Subroutine -> Test if the privacy get_users_in_context of the provider works
     *
     * @param \stdClass $user the user to idenitfy the right context
     * @return bool if the user of the context of the system (always 1) is the same, as queried over the API
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function privacy_get_users_sub_test(\stdClass $user) : bool {
        $privacy = new privacy\provider();
        $context = new contextlist();
        $context->add_system_context();
        $ul = new \core_privacy\local\request\userlist($context->get_contexts()[0], "lytix/grademonitor");
        return $privacy::get_users_in_context($ul)->get_userids()[0] == $user->id;
    }

    /**
     * Subroutine -> Test if the privacy get_contexts_for_userid of the provider works
     *
     * @param \stdClass $user
     * @param \stdClass $context
     * @return bool
     */
    public static function privacy_get_context_sub_test(\stdClass $user, \stdClass $context) : bool {
        // Contextid by parameter because we need the context of the course.
        $privacy = new privacy\provider();
        $contexts = array_flip($privacy::get_contexts_for_userid($user->id)->get_contextids());
        return array_key_exists($context->id, $contexts);
    }

    /**
     * Subroutine -> test if all users from the context (equals all entries/whole table) is emptied
     *
     * @return bool if the table is empty, everything has been deleted
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function privacy_delete_context_sub_test() : bool {
        global $DB;
        $privacy = new privacy\provider();
        $privacy::delete_data_for_all_users_in_context(\context_system::instance());
        return empty($DB->get_records_sql("SELECT * FROM {lytix_grademonitor}"));
    }

    /**
     * Subroutine -> check if metadata is usable and right
     *
     * @return bool if the amount of entries is right, all OK
     */
    public static function privacy_get_metadata_sub_test() : bool {
        $collection = new collection("lytix_grademonitor");
        $privacy = new privacy\provider();
        $collection = $privacy::get_metadata($collection);

        return empty($collection->get_collection()) ? false :
            (count($collection->get_collection()[0]->get_privacy_fields()) == 8 ? true : false);
    }

    /**
     * Subroutine -> test if all users from the context (equals all entries/whole table) is emptied
     *
     * @param \stdClass $user for deletion
     * @return bool if the table is empty, everything has been deleted
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function privacy_delete_context_user_sub_test(\stdClass $user) : bool {
        global $DB;
        $privacy = new privacy\provider();
        // Contextid always hardcoded 1 CONTEXT_SYSTEM first entry.
        $contextlist = new approved_contextlist($user, "lytix_grademonitor", [1]);
        $privacy::delete_data_for_user($contextlist);
        return empty($DB->get_records_sql("SELECT * FROM {lytix_grademonitor}"));
    }

    /**
     * * Subroutine -> export all userdata for context.
     *
     * @param \stdClass $user
     * @return void
     */
    public static function privacy_export_user_data_sub_test(\stdClass $user) {
        $privacy = new privacy\provider();
        // Contextid always hardcoded 1 CONTEXT_SYSTEM first entry.
        $contextlist = new approved_contextlist($user, "lytix_grademonitor", [1]);
        $privacy::export_user_data($contextlist);
    }
}
