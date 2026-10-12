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
 * Event observers for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock;

/**
 * Event observers of the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * A course was deleted: every plugin row of that course goes with it.
     *
     * @param \core\event\course_deleted $event The event.
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        \local_eftimeclock\local\coursedelete::purge((int) $event->objectid);
    }

    /**
     * A course was reset unenrolling its STUDENTS (a new edition): the time
     * measured in the old one goes, so a repeating student starts from zero.
     * "Unenrol users" is a list of role ids in the core form: a reset that
     * keeps the students (or unenrols only teachers) keeps their time.
     *
     * @param \core\event\course_reset_ended $event The event.
     */
    public static function course_reset_ended(\core\event\course_reset_ended $event): void {
        if (self::unenrols_students($event)) {
            \local_eftimeclock\local\coursedelete::reset((int) $event->courseid);
        }
    }

    /**
     * Whether a reset unenrolled a student-archetype role.
     *
     * @param \core\event\course_reset_ended $event The event.
     * @return bool
     */
    public static function unenrols_students(\core\event\course_reset_ended $event): bool {
        global $DB;
        $options = (array) ($event->other['reset_options'] ?? []);
        $roleids = array_filter(array_map('intval', (array) ($options['unenrol_users'] ?? [])));
        if (!$roleids) {
            return false;
        }
        [$insql, $params] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params['archetype'] = 'student';
        return $DB->record_exists_select('role', "id $insql AND archetype = :archetype", $params);
    }
}
