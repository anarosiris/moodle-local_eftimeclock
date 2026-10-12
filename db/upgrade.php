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
 * Upgrade steps for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs the upgrade steps between versions.
 *
 * @param int $oldversion Version installed before the upgrade.
 * @return bool
 */
function xmldb_local_eftimeclock_upgrade($oldversion): bool {
    if ($oldversion < 2026081100) {
        // Course-deletion doctrine (SPEC_BORRADO_CURSO.md in local_efreport):
        // clean the rows orphaned by courses deleted before the
        // course_deleted observer existed.
        \local_eftimeclock\local\coursedelete::purge_orphans();

        upgrade_plugin_savepoint(true, 2026081100, 'local', 'eftimeclock');
    }

    return true;
}
