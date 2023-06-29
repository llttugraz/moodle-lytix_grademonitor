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

use gradereport_history\output\tablelog;

/**
 * Grademonitor History Class.
 */
class grademonitor_history extends tablelog {
    /**
     * Overwritten constructor.
     *
     * @param string $uniqueid unique id of table.
     * @param \context_course $context Context of the report.
     * @param \moodle_url $url url of the page where this table would be displayed.
     * @param array $filters options are:
     *                          userids : limit to specific users (default: none)
     *                          itemid : limit to specific grade item (default: all)
     *                          grader : limit to specific graders (default: all)
     *                          datefrom : start of date range
     *                          datetill : end of date range
     *                          revisedonly : only show revised grades (default: false)
     *                          format : page | csv | excel (default: page)
     * @param string $download Represents download format, pass '' no download at this time.
     * @param int $page The current page being displayed.
     * @param int $perpage Number of rules to display per pages.
     */
    public function __construct($uniqueid, \context_course $context, $url, $filters = array(),
                                $download = '', $page = 0, $perpage = 100) {
        parent::__construct($uniqueid, $context, $url, $filters, $download, $page, $perpage);
        parent::setup();
    }

    /**
     * Overwritten query_db function.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        list($sql, $params) = $this->get_sql_and_params();

        $histories = $DB->get_records_sql($sql, $params);
        $result = [];
        foreach ($histories as $history) {
            if ($history->itemtype == 'course') {
                if (key_exists($history->userid, $result)) {
                    if ($result[$history->userid]->timemodified <= $history->timemodified) {
                        $result[$history->userid] = $history;
                    }
                } else {
                    $result[$history->userid] = $history;
                }
            }
        }
        $this->rawdata = $result;
        return $result;
    }
}
