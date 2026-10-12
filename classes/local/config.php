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
 * Effective clock configuration for a course.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

/**
 * Effective configuration of the free clock: ON/OFF and theme, per course,
 * with site-wide defaults. The full theme catalogue of the suite is shown in
 * the gallery, but the free plugin only stores the two free themes.
 *
 * With the PRO suite (local_efreport) installed the free clock steps aside
 * on the whole site: it neither shows nor records, and it says so to whoever
 * asks (the certificate plugin, the settings page), so nobody takes a frozen
 * total for a live measurement.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {
    /** @var array<int,array> Per-request cache of the effective configuration, by course. */
    private static array $cache = [];

    /** @var bool|null Test hook: pretend the PRO suite is (not) installed. */
    private static ?bool $prooverride = null;

    /**
     * Themes included in the free plugin (keys identical to the PRO suite, so
     * that migration keeps the chosen appearance).
     *
     * @return string[]
     */
    public static function free_themes(): array {
        return ['zafiro', 'pizarra'];
    }

    /**
     * Full catalogue of the suite (those not in free_themes() are shown
     * greyed out with the PRO label in the gallery).
     *
     * @return string[]
     */
    public static function all_themes(): array {
        return ['zafiro', 'pizarra', 'circulo', 'claro', 'bosque', 'atardecer',
            'pildora', 'minimo', 'electrico'];
    }

    /**
     * Whether the PRO suite measures instead of this plugin (coexistence
     * during the migration: a single clock, a single measurement). Only the
     * existence of the class is checked; nothing of PRO is ever called.
     *
     * @return bool
     */
    public static function superseded(): bool {
        if (self::$prooverride !== null) {
            return self::$prooverride;
        }
        return class_exists('\local_efreport\local\config');
    }

    /**
     * Tests only: pretend the PRO suite is present or absent (null = look it up).
     *
     * @param bool|null $present Whether to behave as if local_efreport were installed.
     * @return void
     */
    public static function set_superseded_for_tests(?bool $present): void {
        self::$prooverride = $present;
        self::reset_caches();
    }

    /**
     * Resets the per-request cache (after saving, in tests).
     *
     * @return void
     */
    public static function reset_caches(): void {
        self::$cache = [];
    }

    /**
     * Configuration applicable to a course.
     *
     * @param int $courseid Course.
     * @return array ['enabled' => bool, 'configured' => bool, 'theme' => string, 'superseded' => bool,
     *               'ownsettings' => bool] enabled = the clock measures here; configured = what the
     *               course (or the site) asks for; superseded = PRO measures instead (enabled is then
     *               false whatever the course says); ownsettings = the course has its own row.
     */
    public static function for_course(int $courseid): array {
        global $DB;
        if (isset(self::$cache[$courseid])) {
            return self::$cache[$courseid];
        }

        $enabled = get_config('local_eftimeclock', 'default_enabled');
        $enabled = ($enabled === false) ? true : (bool) $enabled;
        $theme = (string) get_config('local_eftimeclock', 'clocktheme');
        if (!in_array($theme, self::free_themes(), true)) {
            $theme = 'zafiro';
        }

        $row = $DB->get_record('local_eftimeclock_cfg', ['courseid' => $courseid]);
        if ($row) {
            $enabled = (bool) $row->enabled;
            if (in_array($row->clocktheme, self::free_themes(), true)) {
                $theme = $row->clocktheme;
            }
        }
        $superseded = self::superseded();

        self::$cache[$courseid] = [
            'enabled'     => $enabled && !$superseded,
            'configured'  => $enabled,
            'theme'       => $theme,
            'superseded'  => $superseded,
            'ownsettings' => (bool) $row,
        ];
        return self::$cache[$courseid];
    }

    /**
     * Saves the configuration of a course (upsert).
     *
     * @param int $courseid Course.
     * @param bool $enabled Clock enabled.
     * @param string $theme Theme (only the free ones are accepted).
     * @return void
     */
    public static function save_course(int $courseid, bool $enabled, string $theme): void {
        global $DB;
        if (!in_array($theme, self::free_themes(), true)) {
            $theme = 'zafiro';
        }
        $row = $DB->get_record('local_eftimeclock_cfg', ['courseid' => $courseid]);
        if ($row) {
            $row->enabled = (int) $enabled;
            $row->clocktheme = $theme;
            $row->timemodified = time();
            $DB->update_record('local_eftimeclock_cfg', $row);
        } else {
            $DB->insert_record('local_eftimeclock_cfg', (object) [
                'courseid'     => $courseid,
                'enabled'      => (int) $enabled,
                'clocktheme'   => $theme,
                'timemodified' => time(),
            ]);
        }
        unset(self::$cache[$courseid]);
    }

    /**
     * Forgets the course's own settings: it follows the site values again.
     *
     * @param int $courseid Course.
     * @return void
     */
    public static function clear_course(int $courseid): void {
        global $DB;
        $DB->delete_records('local_eftimeclock_cfg', ['courseid' => $courseid]);
        unset(self::$cache[$courseid]);
    }
}
