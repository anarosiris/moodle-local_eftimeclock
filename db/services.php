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
 * External functions for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_eftimeclock_record_time' => [
        'classname'   => 'local_eftimeclock\external\record_time',
        'description' => 'Stores the active time of the current visit (idempotent per client token).',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'local_eftimeclock_acknowledge_consent' => [
        'classname'   => 'local_eftimeclock\external\acknowledge_consent',
        'description' => 'Records that the user has seen the measurement notice.',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
