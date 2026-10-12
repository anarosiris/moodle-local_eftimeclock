<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Time queries used by the clock widget and the simple report.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

/**
 * Time queries: the clock and the report ALWAYS read from here, so they
 * never count differently.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeapi {
    /**
     * Seconds accumulated by a user in a course.
     *
     * @param int $userid User.
     * @param int $courseid Course.
     * @return int
     */
    public static function get_course_time(int $userid, int $courseid): int {
        global $DB;
        return (int) $DB->get_field_sql(
            "SELECT COALESCE(SUM(duration), 0)
               FROM {local_eftimeclock_session}
              WHERE userid = :userid AND courseid = :courseid",
            ['userid' => $userid, 'courseid' => $courseid]
        );
    }

    /**
     * Per-student summary for the simple report: total, first and last
     * measured session. A single aggregate query (shared-hosting diet).
     * Visits that never got a second (the first save was refused) are not
     * an access.
     *
     * @param int $courseid Course.
     * @param int[] $userids Students of the report.
     * @return array userid => {total, first, last}
     */
    public static function course_summary(int $courseid, array $userids): array {
        global $DB;
        if (empty($userids)) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_sql(
            "SELECT userid,
                    COALESCE(SUM(duration), 0) AS total,
                    MIN(timecreated) AS firstseen,
                    MAX(timemodified) AS lastseen
               FROM {local_eftimeclock_session}
              WHERE courseid = :courseid AND duration > 0 AND userid $insql
           GROUP BY userid",
            ['courseid' => $courseid] + $inparams
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->userid] = (object) [
                'total' => (int) $row->total,
                'first' => (int) $row->firstseen,
                'last'  => (int) $row->lastseen,
            ];
        }
        return $out;
    }

    /**
     * Users with measured time in a course (whatever their role today):
     * the report must list everybody whose time it holds.
     *
     * @param int $courseid Course.
     * @return int[] User ids.
     */
    public static function measured_users(int $courseid): array {
        global $DB;
        return array_map('intval', $DB->get_fieldset_sql(
            "SELECT DISTINCT userid FROM {local_eftimeclock_session} WHERE courseid = :courseid AND duration > 0",
            ['courseid' => $courseid]
        ));
    }

    /**
     * Formats seconds as "3h 25m" (or "45m", or "12s").
     *
     * @param int $seconds Seconds.
     * @return string
     */
    public static function format_time(int $seconds): string {
        if ($seconds <= 0) {
            return '—';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h > 0) {
            return $h . 'h ' . $m . 'm';
        }
        if ($m > 0) {
            $s = $seconds % 60;
            return $m . 'm ' . $s . 's';
        }
        return $seconds . 's';
    }
}
