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
 * Library callbacks for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the report and the configuration to the course menu ("More").
 *
 * @param navigation_node $navigation Course node.
 * @param stdClass $course Course.
 * @param context_course $context Context.
 * @return void
 */
function local_eftimeclock_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/eftimeclock:viewreport', $context)) {
        $navigation->add(
            get_string('report_title', 'local_eftimeclock'),
            new moodle_url('/local/eftimeclock/report.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'eftimeclockreport',
            new pix_icon('i/scheduled', '')
        );
    }
    if (has_capability('local/eftimeclock:manage', $context)) {
        $navigation->add(
            get_string('coursesettings', 'local_eftimeclock'),
            new moodle_url('/local/eftimeclock/coursesettings.php', ['courseid' => $course->id]),
            navigation_node::TYPE_SETTING,
            null,
            'eftimeclockcfg',
            new pix_icon('i/settings', '')
        );
    }
}

/**
 * Registers the status checks of the plugin (file integrity).
 *
 * @return \core\check\check[]
 */
function local_eftimeclock_status_checks(): array {
    return [new \local_eftimeclock\check\files()];
}
