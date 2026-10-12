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
 * Restore support for local_eftimeclock (config + measured time).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the clock configuration (upsert per course) and the time sessions
 * (remapping user and cmid; unmappable users are SKIPPED — err on the low
 * side). Idempotent: dedup by natural key (clienttoken).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_eftimeclock_plugin extends restore_local_plugin {
    /**
     * Course-level paths.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [
            new restore_path_element('eftimeclock_cfg', $this->get_pathfor('/eftimeclock_cfg')),
        ];
    }

    /**
     * Module-level paths.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure() {
        return [
            new restore_path_element(
                'eftimeclock_session',
                $this->get_pathfor('/eftimeclock_sessions/eftimeclock_session')
            ),
        ];
    }

    /**
     * Processes the course configuration (upsert: restoring twice does not duplicate).
     *
     * @param array $data Row data.
     * @return void
     */
    public function process_eftimeclock_cfg($data) {
        global $DB;
        $data = (object) $data;
        $courseid = (int) $this->task->get_courseid();
        // Only a known free theme is restored (the column is a closed list).
        $theme = in_array((string) $data->clocktheme, \local_eftimeclock\local\config::free_themes(), true)
            ? (string) $data->clocktheme : 'zafiro';

        $existing = $DB->get_record('local_eftimeclock_cfg', ['courseid' => $courseid]);
        if ($existing) {
            $existing->enabled = (int) (bool) $data->enabled;
            $existing->clocktheme = $theme;
            $existing->timemodified = time();
            $DB->update_record('local_eftimeclock_cfg', $existing);
        } else {
            $DB->insert_record('local_eftimeclock_cfg', (object) [
                'courseid'     => $courseid,
                'enabled'      => (int) (bool) $data->enabled,
                'clocktheme'   => $theme,
                'timemodified' => time(),
            ]);
        }
        \local_eftimeclock\local\config::reset_caches();
    }

    /**
     * Processes one time session.
     *
     * @param array $data Row data.
     * @return void
     */
    public function process_eftimeclock_session($data) {
        global $DB;
        $data = (object) $data;

        $userid = $this->get_mappingid('user', $data->userid);
        if (!$userid) {
            return; // Unmappable user: skipped (err on the low side).
        }
        $courseid = (int) $this->task->get_courseid();
        $cmid = (int) $this->task->get_moduleid();
        // The same bounds as a live save: a hand-made backup injects no hours.
        $duration = max(0, min((int) $data->duration, \local_eftimeclock\external\record_time::MAX_DURATION));
        $token = (string) ($data->clienttoken ?? '');
        if (\core_text::strlen($token) > \local_eftimeclock\external\record_time::TOKEN_MAXLENGTH) {
            $token = '';
        }

        // Dedup by natural key: restoring twice does not duplicate a single second.
        if (
            $token !== ''
                && $DB->record_exists('local_eftimeclock_session', [
                    'userid' => $userid, 'courseid' => $courseid,
                    'cmid' => $cmid, 'clienttoken' => $token,
                ])
        ) {
            return;
        }
        $DB->insert_record('local_eftimeclock_session', (object) [
            'userid'       => $userid,
            'courseid'     => $courseid,
            'cmid'         => $cmid,
            'duration'     => $duration,
            'clienttoken'  => $token !== '' ? $token : null,
            'timecreated'  => (int) $data->timecreated,
            'timemodified' => (int) $data->timemodified,
        ]);
    }
}
