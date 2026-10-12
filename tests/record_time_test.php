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
 * Tests for the record_time external function.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock;

use local_eftimeclock\external\record_time;
use local_eftimeclock\local\config;
use local_eftimeclock\local\timeapi;

/**
 * Idempotency of the save (visit token), the server-side bound of what a
 * save may add, and the security boundaries (enrolment, capability, role,
 * course switched off).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(record_time::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(timeapi::class)]
final class record_time_test extends \advanced_testcase {
    /**
     * The free clock measures in these tests even where the PRO suite is in the tree.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        config::set_superseded_for_tests(false);
    }

    /**
     * Back to the real lookup.
     */
    protected function tearDown(): void {
        config::set_superseded_for_tests(null);
        parent::tearDown();
    }

    /**
     * Prepares course, activity and student.
     *
     * @return array [course, cm, student]
     */
    private function setup_course(): array {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', ['course' => $course->id]);
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        return [$course, get_coursemodule_from_instance('page', $page->id), $student];
    }

    /**
     * Moves the latest save of a user back in time (as if it happened that many seconds ago).
     *
     * @param int $userid User.
     * @param int $secondsago Seconds.
     * @return void
     */
    private function age_saves(int $userid, int $secondsago): void {
        global $DB;
        $DB->execute(
            'UPDATE {local_eftimeclock_session} SET timemodified = timemodified - :s WHERE userid = :u',
            ['s' => $secondsago, 'u' => $userid]
        );
    }

    /**
     * All submissions of a visit (same token) land on the same row and the
     * duration is stored with max() — a duplicate sendBeacon does not inflate.
     *
     * @return void
     */
    public function test_idempotent_upsert_by_clienttoken(): void {
        global $DB;
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);

        $token = str_repeat('a', 32);

        // First submission (XHR): creates the row.
        $r1 = record_time::execute((int) $course->id, (int) $cm->id, 0, 30, $token);
        // A minute later, the repeated beacon with the accumulated time (no sessionid): SAME row.
        $this->age_saves((int) $student->id, 60);
        $r2 = record_time::execute((int) $course->id, (int) $cm->id, 0, 90, $token);
        // Late beacon with a SMALLER accumulated time: does not reduce (max).
        $r3 = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, $token);

        $this->assertEquals($r1['sessionid'], $r2['sessionid']);
        $this->assertEquals($r1['sessionid'], $r3['sessionid']);
        $this->assertEquals(1, $DB->count_records('local_eftimeclock_session'));
        $this->assertEquals(90, timeapi::get_course_time((int) $student->id, (int) $course->id));
    }

    /**
     * Different visits (different tokens) add up as separate rows.
     *
     * @return void
     */
    public function test_different_visits_accumulate(): void {
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);

        record_time::execute((int) $course->id, (int) $cm->id, 0, 120, str_repeat('a', 32));
        $this->age_saves((int) $student->id, 60);
        record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('b', 32));

        $this->assertEquals(180, timeapi::get_course_time((int) $student->id, (int) $course->id));
    }

    /**
     * The client is not trusted with the amount: a first save carries at
     * most the first-save ceiling, a later save adds at most the seconds
     * elapsed since the user's latest save, and nothing is ever negative.
     *
     * @return void
     */
    public function test_saves_are_bounded_by_the_server_clock(): void {
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);
        $uid = (int) $student->id;
        $cid = (int) $course->id;

        // A visit cannot be born with 24 hours.
        $r = record_time::execute($cid, (int) $cm->id, 0, 999999999, str_repeat('c', 32));
        $this->assertEquals(record_time::FIRST_SAVE_MAX, $r['duration']);
        $negative = record_time::execute($cid, (int) $cm->id, 0, -50, str_repeat('n', 32));
        $this->assertEquals(0, $negative['duration']);

        // 30 seconds later the client claims +600: only 30 are credited (a
        // second may tick between the steps: one more at most).
        $this->age_saves($uid, 30);
        $r = record_time::execute($cid, (int) $cm->id, $r['sessionid'], record_time::FIRST_SAVE_MAX + 600, str_repeat('c', 32));
        $this->assertEqualsWithDelta(record_time::FIRST_SAVE_MAX + 30, $r['duration'], 1);
        $stored = $r['duration'];

        // A replay of the same cumulative value adds nothing, however long ago.
        $this->age_saves($uid, 3600);
        $r = record_time::execute($cid, (int) $cm->id, $r['sessionid'], $stored, str_repeat('c', 32));
        $this->assertEquals($stored, $r['duration']);

        // A new visit right after another save starts with what has elapsed (nothing).
        $r = record_time::execute($cid, (int) $cm->id, 0, 120, str_repeat('d', 32));
        $this->assertLessThanOrEqual(1, $r['duration']);
        $this->assertEqualsWithDelta($stored, timeapi::get_course_time($uid, $cid), 1);
    }

    /**
     * Two devices at once (two visits interleaving their saves) can never
     * credit more than the wall-clock time between them.
     *
     * @return void
     */
    public function test_overlapping_visits_do_not_double_count(): void {
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);
        $uid = (int) $student->id;
        $cid = (int) $course->id;

        $phone = record_time::execute($cid, (int) $cm->id, 0, 60, str_repeat('p', 32));
        $this->age_saves($uid, 30);
        $laptop = record_time::execute($cid, (int) $cm->id, 0, 60, str_repeat('l', 32));
        // Each device keeps claiming a full minute per minute; only 30 s per save can be real.
        for ($i = 0; $i < 4; $i++) {
            $this->age_saves($uid, 30);
            record_time::execute($cid, (int) $cm->id, $phone['sessionid'], 60 + 60 * ($i + 1), str_repeat('p', 32));
            $this->age_saves($uid, 30);
            record_time::execute($cid, (int) $cm->id, $laptop['sessionid'], 60 + 60 * ($i + 1), str_repeat('l', 32));
        }
        // 60 + 30 (first saves) + 8 saves of 30 s = 330 s for 4.5 minutes of
        // wall clock, not 600 (a second may tick between steps: ten more at most).
        $this->assertEqualsWithDelta(60 + 30 + 8 * 30, timeapi::get_course_time($uid, $cid), 10);
    }

    /**
     * A sessionid of another course or activity (stale client state) never
     * receives the seconds of this visit.
     *
     * @return void
     */
    public function test_session_of_another_course_is_not_reused(): void {
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);
        $gen = $this->getDataGenerator();
        $other = $gen->create_course();
        $gen->enrol_user($student->id, $other->id, 'student');
        $otherpage = $gen->create_module('page', ['course' => $other->id]);

        $first = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('a', 32));
        $this->age_saves((int) $student->id, 120);
        record_time::execute((int) $other->id, (int) $otherpage->cmid, $first['sessionid'], 100, str_repeat('b', 32));

        $this->assertEquals(60, timeapi::get_course_time((int) $student->id, (int) $course->id));
        $this->assertEquals(100, timeapi::get_course_time((int) $student->id, (int) $other->id));
    }

    /**
     * Without an active enrolment or without the capability nothing is saved;
     * a cmid from another course does not slip through either; staff (report
     * viewers) and administrators record nothing even if enrolled; a token
     * longer than the column is refused, not stored.
     *
     * @return void
     */
    public function test_security_boundaries(): void {
        global $DB;
        [$course, $cm, $student] = $this->setup_course();
        $gen = $this->getDataGenerator();

        // User NOT enrolled.
        $stranger = $gen->create_user();
        $this->setUser($stranger);
        try {
            $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('d', 32));
            $this->assertEquals(0, $r['duration']);
        } catch (\moodle_exception $e) {
            // NOTE: validate_context may deny earlier; that is also a valid outcome.
            $this->assertNotEmpty($e);
        }
        $this->assertEquals(0, $DB->count_records('local_eftimeclock_session'));

        // A cmid from ANOTHER course: rejected.
        $other = $gen->create_course();
        $otherpage = $gen->create_module('page', ['course' => $other->id]);
        $othercm = get_coursemodule_from_instance('page', $otherpage->id);
        $this->setUser($student);
        $r = record_time::execute((int) $course->id, (int) $othercm->id, 0, 60, str_repeat('e', 32));
        $this->assertEquals(0, $r['duration']);

        // A teacher who is also enrolled as a student, and the administrator: not measured.
        $teacher = $gen->create_user();
        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $gen->enrol_user($teacher->id, $course->id, 'student');
        $this->setUser($teacher);
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('t', 32));
        $this->assertEquals(0, $r['duration']);
        $this->setAdminUser();
        $gen->enrol_user((int) get_admin()->id, $course->id, 'student');
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('m', 32));
        $this->assertEquals(0, $r['duration']);

        // A token longer than the column: refused without a database error.
        $this->setUser($student);
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('x', 40));
        $this->assertEquals(0, $r['duration']);
        $this->assertEquals(0, $DB->count_records('local_eftimeclock_session'));
    }

    /**
     * Clock switched off in the course, or the PRO suite measuring instead:
     * the endpoint records nothing, whatever the client sends.
     *
     * @return void
     */
    public function test_off_or_superseded_records_nothing(): void {
        global $DB;
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);

        config::save_course((int) $course->id, false, 'zafiro');
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('a', 32));
        $this->assertEquals(0, $r['duration']);

        config::save_course((int) $course->id, true, 'zafiro');
        config::set_superseded_for_tests(true);
        $this->assertFalse(config::for_course((int) $course->id)['enabled']);
        $this->assertTrue(config::for_course((int) $course->id)['configured']);
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('b', 32));
        $this->assertEquals(0, $r['duration']);
        $this->assertEquals(0, $DB->count_records('local_eftimeclock_session'));

        config::set_superseded_for_tests(false);
        $r = record_time::execute((int) $course->id, (int) $cm->id, 0, 60, str_repeat('c', 32));
        $this->assertEquals(60, $r['duration']);
    }

    /**
     * Report summary: total, first and last session per student; a visit
     * that never got a second is not an access; everybody with time is listed.
     *
     * @return void
     */
    public function test_course_summary(): void {
        [$course, $cm, $student] = $this->setup_course();
        $this->setUser($student);

        record_time::execute((int) $course->id, (int) $cm->id, 0, 300, str_repeat('f', 32));
        $summary = timeapi::course_summary((int) $course->id, [(int) $student->id]);

        $this->assertArrayHasKey((int) $student->id, $summary);
        $this->assertEquals(300, $summary[(int) $student->id]->total);
        $this->assertGreaterThan(0, $summary[(int) $student->id]->first);
        $this->assertGreaterThanOrEqual(
            $summary[(int) $student->id]->first,
            $summary[(int) $student->id]->last
        );
        $this->assertSame([(int) $student->id], timeapi::measured_users((int) $course->id));

        // Another student whose first save was refused (0 s): no access, not measured.
        $other = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($other->id, $course->id, 'student');
        $this->setUser($other);
        record_time::execute((int) $course->id, (int) $cm->id, 0, 0, str_repeat('g', 32));
        $this->assertArrayNotHasKey((int) $other->id, timeapi::course_summary((int) $course->id, [(int) $other->id]));
        $this->assertSame([(int) $student->id], timeapi::measured_users((int) $course->id));
    }
}
