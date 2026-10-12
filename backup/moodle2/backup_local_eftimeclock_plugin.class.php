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
 * Backup support: config always; measured time with "Include enrolled users".
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Three-checkbox doctrine: the clock configuration ALWAYS travels; the
 * measured time (student data) only with "Include enrolled users".
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_eftimeclock_plugin extends backup_local_plugin {
    /**
     * Course-level structure: the clock configuration, always.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        // Names UNIQUE among plugins of the same attachment point (optigroup).
        $cfg = new backup_nested_element('eftimeclock_cfg', ['id'], [
            'enabled', 'clocktheme', 'timemodified',
        ]);
        $wrapper->add_child($cfg);
        $cfg->set_source_table('local_eftimeclock_cfg', ['courseid' => backup::VAR_COURSEID]);

        return $plugin;
    }

    /**
     * Module-level structure: the time sessions, only with user info.
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $sessions = new backup_nested_element('eftimeclock_sessions');
        $session = new backup_nested_element('eftimeclock_session', ['id'], [
            'userid', 'duration', 'clienttoken', 'timecreated', 'timemodified',
        ]);
        $wrapper->add_child($sessions);
        $sessions->add_child($session);

        if ($this->get_setting_value('userinfo')) {
            $session->set_source_table('local_eftimeclock_session', ['cmid' => backup::VAR_MODID]);
        }
        $session->annotate_ids('user', 'userid');

        return $plugin;
    }
}
