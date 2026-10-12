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
 * Tests for the course deletion cleanup of local_eftimeclock.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Course deletion doctrine: everything of the course goes, nothing else does.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(coursedelete::class)]
final class coursedelete_test extends \advanced_testcase {
    /**
     * Inserts a row filling every column generically from the schema
     * (0 / '' by type), with the given overrides.
     *
     * @param string $table Table name.
     * @param array $overrides Column => value to force.
     * @return int New record id.
     */
    protected function minimal_record(string $table, array $overrides): int {
        global $DB;
        $record = [];
        foreach ($DB->get_columns($table) as $name => $col) {
            if ($name === 'id') {
                continue;
            }
            $record[$name] = in_array($col->meta_type, ['C', 'X'], true) ? '' : 0;
        }
        // Only apply overrides for columns the table really has.
        $overrides = array_intersect_key($overrides, $record);
        return (int) $DB->insert_record($table, (object) array_merge($record, $overrides));
    }

    /**
     * Inserts one minimal row per plugin table for the given course.
     *
     * Unique-ish columns (cmid, tokens) vary with the course so two seeded
     * courses never collide.
     *
     * @param int $courseid Course id.
     */
    protected function seed_rows(int $courseid): void {
        foreach (coursedelete::TABLES as $table) {
            $this->minimal_record($table, [
                'courseid' => $courseid,
                'cmid' => $courseid,
                'clienttoken' => 'tok' . $courseid,
            ]);
        }
    }

    /**
     * Rows of the course in every plugin table.
     *
     * @param int $courseid Course id.
     * @return int Total rows.
     */
    protected function count_rows(int $courseid): int {
        global $DB;
        $total = 0;
        foreach (coursedelete::TABLES as $table) {
            $total += $DB->count_records($table, ['courseid' => $courseid]);
        }
        return $total;
    }

    /**
     * Deleting a course purges every plugin row of it; other courses stay.
     */
    public function test_course_deleted_purges_everything(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $doomed = $gen->create_course();
        $control = $gen->create_course();
        $this->seed_rows((int) $doomed->id);
        $this->seed_rows((int) $control->id);

        $this->assertSame(2, $this->count_rows((int) $doomed->id));

        delete_course($doomed, false);

        $this->assertSame(0, $this->count_rows((int) $doomed->id));
        $this->assertSame(2, $this->count_rows((int) $control->id));
    }

    /**
     * The upgrade cleanup removes orphaned rows only.
     */
    public function test_purge_orphans(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->seed_rows((int) $course->id);
        $this->seed_rows(999999); // Orphan: course does not exist.

        coursedelete::purge_orphans();

        $this->assertSame(0, $this->count_rows(999999));
        $this->assertSame(2, $this->count_rows((int) $course->id));
    }

    /**
     * has_data(): only auditable evidence warrants the deletion warning.
     */
    public function test_has_data(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse(coursedelete::has_data((int) $course->id));

        // Config alone does not count...
        $this->minimal_record('local_eftimeclock_cfg', ['courseid' => (int) $course->id]);
        $this->assertFalse(coursedelete::has_data((int) $course->id));

        // ...a visit without a second (first save refused) neither...
        $this->minimal_record('local_eftimeclock_session', [
            'courseid' => (int) $course->id, 'clienttoken' => 'tok0',
        ]);
        $this->assertFalse(coursedelete::has_data((int) $course->id));

        // ...auditable evidence does.
        $this->minimal_record('local_eftimeclock_session', [
            'courseid' => (int) $course->id, 'clienttoken' => 'tok', 'duration' => 30,
        ]);
        $this->assertTrue(coursedelete::has_data((int) $course->id));
    }

    /**
     * Resetting the course unenrolling its students deletes their measured
     * time and keeps the settings; a reset that keeps the students (or
     * unenrols only teachers) keeps the time.
     */
    public function test_course_reset_with_students_unenrolled_resets_the_time(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        $this->seed_rows((int) $course->id);
        $DB->set_field('local_eftimeclock_session', 'userid', $student->id, ['courseid' => $course->id]);
        $studentrole = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
        $teacherrole = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

        // Reset without unenrolling, then unenrolling teachers only: nothing changes.
        foreach ([[], [$teacherrole]] as $roles) {
            $data = (object) ['id' => $course->id, 'courseid' => $course->id, 'reset_start_date' => 0];
            if ($roles) {
                $data->unenrol_users = $roles;
            }
            reset_course_userdata($data);
            $this->assertSame(2, $this->count_rows((int) $course->id));
        }

        // Unenrolling the students: the time goes, the settings stay.
        reset_course_userdata((object) [
            'id' => $course->id, 'courseid' => $course->id, 'reset_start_date' => 0, 'unenrol_users' => [$studentrole],
        ]);
        $this->assertSame(0, $DB->count_records('local_eftimeclock_session', ['courseid' => $course->id]));
        $this->assertSame(1, $DB->count_records('local_eftimeclock_cfg', ['courseid' => $course->id]));
    }
}
