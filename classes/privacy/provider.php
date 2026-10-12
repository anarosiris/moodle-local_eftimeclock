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
 * Privacy provider for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The plugin stores the active time per user and activity, plus one user
 * preference (measurement notice seen).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Declares the personal data stored by the plugin.
     *
     * @param collection $collection Collection to fill in.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_eftimeclock_session', [
            'userid'       => 'privacy:metadata:local_eftimeclock_session:userid',
            'courseid'     => 'privacy:metadata:local_eftimeclock_session:courseid',
            'cmid'         => 'privacy:metadata:local_eftimeclock_session:cmid',
            'duration'     => 'privacy:metadata:local_eftimeclock_session:duration',
            'clienttoken'  => 'privacy:metadata:local_eftimeclock_session:clienttoken',
            'timecreated'  => 'privacy:metadata:local_eftimeclock_session:timecreated',
            'timemodified' => 'privacy:metadata:local_eftimeclock_session:timemodified',
        ], 'privacy:metadata:local_eftimeclock_session');
        $collection->add_user_preference(
            'local_eftimeclock_consent',
            'privacy:metadata:preference:consent'
        );
        return $collection;
    }

    /**
     * Contexts (courses) where the user has data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_eftimeclock_session} s ON s.courseid = ctx.instanceid
                       AND ctx.contextlevel = :courselevel
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['courselevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist List to fill in.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            "SELECT userid FROM {local_eftimeclock_session} WHERE courseid = :courseid",
            ['courseid' => $context->instanceid]
        );
    }

    /**
     * Exports the user's data: one readable line per visit (activity,
     * seconds, when), never the raw rows.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $sessions = $DB->get_records(
                'local_eftimeclock_session',
                ['courseid' => $context->instanceid, 'userid' => $userid],
                'timecreated'
            );
            if (!$sessions) {
                continue;
            }
            $modinfo = get_fast_modinfo((int) $context->instanceid);
            $visits = [];
            foreach ($sessions as $session) {
                $cm = $modinfo->cms[(int) $session->cmid] ?? null;
                $visits[] = (object) [
                    'activity'     => $cm ? format_string($cm->name) : (string) $session->cmid,
                    'seconds'      => (int) $session->duration,
                    'timecreated'  => transform::datetime((int) $session->timecreated),
                    'timemodified' => transform::datetime((int) $session->timemodified),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_eftimeclock')],
                (object) ['visits' => $visits]
            );
        }
    }

    /**
     * Exports the user preference (measurement notice acknowledged).
     *
     * @param int $userid User id.
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        $consent = get_user_preferences('local_eftimeclock_consent', null, $userid);
        if ($consent === null) {
            return;
        }
        writer::export_user_preference(
            'local_eftimeclock',
            'local_eftimeclock_consent',
            transform::yesno((bool) $consent),
            get_string('privacy:metadata:preference:consent', 'local_eftimeclock')
        );
    }

    /**
     * Deletes the data of all users in a context (course).
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_course) {
            return;
        }
        $DB->delete_records('local_eftimeclock_session', ['courseid' => $context->instanceid]);
    }

    /**
     * Deletes the data of one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $DB->delete_records(
                'local_eftimeclock_session',
                ['courseid' => $context->instanceid, 'userid' => $userid]
            );
        }
    }

    /**
     * Deletes the data of several users in a context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'local_eftimeclock_session',
            "courseid = :courseid AND userid $insql",
            $inparams + ['courseid' => $context->instanceid]
        );
    }
}
