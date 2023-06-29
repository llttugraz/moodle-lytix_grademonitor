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
 * Grade Monitor plugin for lytix
 *
 * @package    lytix_grademonitor
 * @author     Alexander Kremser
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lytix Grade Monitor';
$string['privacy:metadata'] = 'This plugin does not store any data.';

$string['description'] = 'The <b>Final Goal</b> is the final grade students <em>want</em> to achieve; <b>Self Estimation</b> is what they currently think they <em>can</em> achieve.';

$string['widget_name'] = 'Grade Monitor';

$string['goal'] = 'Your Goal';
$string['goal_empty'] = 'Please select the final grade you want to achieve from the drop-down menu.';
$string['goal_likely'] = 'You are on track to achieving your goal. Keep on going, you can do it!';
$string['goal_unlikely'] = 'You don’t seem to be on the best way to achieving this goal. Overthink this, you’ll find your way.';
$string['goal_unachievable'] = 'Currently, you cannot achieve this goal. Don’t worry, focus, reconsider and carry on. You got this!';
$string['goal_fail'] = 'Currently, you cannot pass this course. Don’t panic, focus, reconsider and carry on. You got this!';

$string['disclaimer'] = 'Please keep in mind: <i>Learner’s Corner</i> is still just a prototype. <strong>Errors in the grade calculation are possible!</strong> When in doubt about your grades, contact your teacher.';

$string['scheme_updated'] = 'The grading scheme has been updated on: ';
$string['dismiss'] = 'dismiss';

$string['th_optional_abbr'] = 'opt.';
$string['th_optional'] = 'optional';
$string['th_weight_abbr'] = 'wght.';
$string['th_weight'] = 'weight: how much this task contributes to the final grade';
$string['th_name'] = 'task';
$string['th_average'] = 'class<br>average';
$string['th_own_result'] = 'your<br>result';
$string['th_estimation'] = 'self estimation';
$string['th_selection'] = 'selection';
$string['th_result'] = 'result';
$string['th_include_abbr'] = 'incl.';
$string['th_include'] = 'include in calculation of estimated final grade';
$string['th_estimate'] = 'estimate';
$string['th_percent'] = 'percentage of total score';
$string['th_goal'] = 'final goal';
$string['th_grade'] = 'current grade';
$string['th_students_per_grade'] = 'students per grade';

$string['points'] = ' pts'; // The space is a thin space.

$string['grade_completion'] = '<b><i>of your final grade is complete</i></b><br>excl. bonus tasks';
$string['current_grade'] = 'your <b>current</b> grade';
$string['class_average'] = 'class average';
$string['self_estimation'] = 'self estimated final grade';
$string['legend'] = 'Tasks with a plus sign + are optional: They only contribute to the final grade if you would pass without the bonus.';
$string['show_average'] = 'show the class average for each task and compared to your current grade';

$string['goal'] = 'Goal';
$string['estimate_abbr'] = 'Est.';
$string['estimate'] = 'Estimation';
$string['grade'] = 'Grade';
$string['percent'] = 'Percent';
$string['week_abbr'] = 'CW';
$string['week'] = 'Calendar Week';
$string['month'] = 'Month';
$string['weeks_month'] = 'Weeks per Month';
$string['today'] = 'today';
$string['show'] = 'Show';
$string['teacher_description'] = '<p>The meaning of <b><i>Percent</i></b> is different for <i>Goal, Estimate and Grade;</i> the respective values answer the following questions.</p><ul><li><b>Goal</b>: How many students have set their goal yet?</li><li><b>Estimate</b>: How many tasks does the median student include in their estimation?</li><li><b>Grade</b>: How many of the median student’s tasks have already been graded?</li></ul>';
// Privacy.
$string['privacy:metadata:lytix_grademonitor'] = "In order to track all activities of the users , we\
 need to save some user related data";
$string['privacy:metadata:lytix_grademonitor:courseid'] = "The course ID will be saved for knowing to which course the\
 data belongs to";
$string['privacy:metadata:lytix_grademonitor:userid'] = "The user ID will be saved for uniquely identifying the user";
$string['privacy:metadata:lytix_grademonitor:goal'] = "Goal";
$string['privacy:metadata:lytix_grademonitor:scheme_update'] = "Gradingscheme changed";
$string['privacy:metadata:lytix_grademonitor:estimations'] = "Selfestimations";
$string['privacy:metadata:lytix_grademonitor:show_others'] = "Show others";
$string['privacy:metadata:lytix_grademonitor:dismiss_notification'] = "Dismiss notificatoins";
$string['privacy:metadata:lytix_grademonitor:timecreated'] = "Timecreated";
$string['privacy:metadata:lytix_grademonitor:future'] = "Future placeholder";
