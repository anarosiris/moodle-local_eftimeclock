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
 * Course deletion cleanup for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

/**
 * Deletes every plugin row of a course when the course is deleted
 * (doctrine agreed on 2026-08-11: SPEC_BORRADO_CURSO.md in local_efreport).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursedelete {
    /**
     * Plugin tables with a courseid column.
     *
     * @var string[]
     */
    public const TABLES = [
        'local_eftimeclock_session',
        'local_eftimeclock_cfg',
    ];

    /**
     * Removes every plugin row of one course.
     *
     * @param int $courseid Course id.
     */
    public static function purge(int $courseid): void {
        global $DB;
        if ($courseid <= 0) {
            return;
        }
        foreach (self::TABLES as $table) {
            $DB->delete_records($table, ['courseid' => $courseid]);
        }
    }

    /**
     * Removes rows orphaned by courses deleted before the observer existed.
     *
     * Called from the upgrade step and reusable by tests.
     */
    public static function purge_orphans(): void {
        global $DB;
        $orphan = 'courseid > 0 AND courseid NOT IN (SELECT id FROM {course})';
        foreach (self::TABLES as $table) {
            $DB->delete_records_select($table, $orphan);
        }
    }

    /**
     * Whether the course holds auditable evidence worth a deletion warning.
     *
     * @param int $courseid Course id.
     * @return bool
     */
    public static function has_data(int $courseid): bool {
        global $DB;
        // A visit without a second (first save refused) is not evidence.
        return $DB->record_exists_select(
            'local_eftimeclock_session',
            'courseid = :courseid AND duration > 0',
            ['courseid' => $courseid]
        );
    }

    /**
     * The course was reset unenrolling its students (a new edition): the
     * measured time of the old one goes; the clock settings stay.
     *
     * @param int $courseid Course id.
     */
    public static function reset(int $courseid): void {
        global $DB;
        if ($courseid <= 0) {
            return;
        }
        $DB->delete_records('local_eftimeclock_session', ['courseid' => $courseid]);
    }
}
