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
 * Site-level settings for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_eftimeclock', get_string('pluginname', 'local_eftimeclock'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_eftimeclock/default_enabled',
        get_string('setting_default_enabled', 'local_eftimeclock'),
        get_string('setting_default_enabled_desc', 'local_eftimeclock'),
        1
    ));

    // The themes of the plugin.
    $settings->add(new admin_setting_configselect(
        'local_eftimeclock/clocktheme',
        get_string('setting_clocktheme', 'local_eftimeclock'),
        get_string('setting_clocktheme_desc', 'local_eftimeclock'),
        'zafiro',
        [
            'zafiro'  => get_string('theme_zafiro', 'local_eftimeclock'),
            'pizarra' => get_string('theme_pizarra', 'local_eftimeclock'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_eftimeclock/showbanner',
        get_string('setting_showbanner', 'local_eftimeclock'),
        get_string('setting_showbanner_desc', 'local_eftimeclock'),
        1
    ));
}
