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
 * Integrity status check: verifies every plugin file against the manifest.
 *
 * The zip built for distribution ships an integrity.json manifest (sha1 and
 * size of every file). This check compares the installed files against it, so
 * a hosting that silently drops or truncates files during installation is
 * detected in Administration > Reports > System checks instead of exploding
 * later with cryptic errors. Development (git) installs carry no manifest and
 * report N/A.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\check;

use core\check\check;
use core\check\result;

/**
 * Verifies the installed plugin files against the shipped integrity manifest.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files extends check {
    /** @var string Frankenstyle component (for the language strings). */
    protected const COMPONENT = 'local_eftimeclock';

    /**
     * Visible name of the check.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('checkfiles', static::COMPONENT);
    }

    /**
     * Root folder of the installed plugin (overridable in tests).
     *
     * @return string
     */
    protected function get_plugin_root(): string {
        return dirname(__DIR__, 2);
    }

    /**
     * Compares every manifest entry against the installed files.
     *
     * @return result
     */
    public function get_result(): result {
        $root = $this->get_plugin_root();
        $manifestfile = $root . '/integrity.json';
        if (!is_readable($manifestfile)) {
            return new result(result::NA, get_string('checkfiles_nomanifest', static::COMPONENT));
        }
        $manifest = json_decode((string) file_get_contents($manifestfile), true);
        if (!is_array($manifest) || empty($manifest['files']) || !is_array($manifest['files'])) {
            return new result(result::WARNING, get_string('checkfiles_badmanifest', static::COMPONENT));
        }

        $bad = [];
        foreach ($manifest['files'] as $rel => $expected) {
            $path = $root . '/' . $rel;
            if (!file_exists($path)) {
                $bad[$rel] = get_string('checkfiles_missing', static::COMPONENT);
            } else if (filesize($path) !== (int) $expected[1] || sha1_file($path) !== $expected[0]) {
                $bad[$rel] = get_string('checkfiles_corrupt', static::COMPONENT);
            }
        }

        if ($bad) {
            $items = [];
            foreach ($bad as $rel => $why) {
                $items[] = s($rel) . ' — ' . $why;
            }
            $details = \html_writer::alist($items)
                . \html_writer::tag('p', get_string('checkfiles_fix', static::COMPONENT));
            return new result(result::ERROR, get_string('checkfiles_error', static::COMPONENT, count($bad)), $details);
        }
        return new result(result::OK, get_string('checkfiles_ok', static::COMPONENT, count($manifest['files'])));
    }
}
