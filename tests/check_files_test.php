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
 * Tests for the plugin file integrity status check.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock;

use core\check\result;
use local_eftimeclock\check\files;

/**
 * The integrity check detects missing, empty and corrupt files.
 *
 * The same pattern (integrity.json manifest in the zip + status check) is used
 * in the six plugins of the suite; the engine is tested here once.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_eftimeclock\check\files
 */
final class check_files_test extends \advanced_testcase {
    /**
     * Creates the check pointing to a test folder instead of the real plugin.
     *
     * @param string $root folder that will act as the plugin root
     * @return files
     */
    private function make_check(string $root): files {
        return new class ($root) extends files {
            /** @var string Test root. */
            private $testroot;

            /**
             * Stores the test root.
             *
             * @param string $testroot simulated root folder
             */
            public function __construct(string $testroot) {
                $this->testroot = $testroot;
            }

            /**
             * Returns the simulated root.
             *
             * @return string
             */
            protected function get_plugin_root(): string {
                return $this->testroot;
            }
        };
    }

    /**
     * Without a manifest (git install) → N/A; with everything intact → OK;
     * with one file deleted and another emptied → ERROR with the count.
     */
    public function test_integrity_statuses(): void {
        $root = make_request_directory();
        $check = $this->make_check($root);

        // Development install: no manifest.
        $this->assertSame(result::NA, $check->get_result()->get_status());

        // Install from zip: manifest + intact files.
        file_put_contents($root . '/a.php', 'contenido-a');
        mkdir($root . '/sub');
        file_put_contents($root . '/sub/b.php', 'contenido-b-largo');
        $manifest = ['component' => 'local_eftimeclock', 'version' => '1', 'files' => [
            'a.php' => [sha1('contenido-a'), strlen('contenido-a')],
            'sub/b.php' => [sha1('contenido-b-largo'), strlen('contenido-b-largo')],
        ]];
        file_put_contents($root . '/integrity.json', json_encode($manifest));
        $this->assertSame(result::OK, $check->get_result()->get_status());

        // The hosting "loses" one and leaves another EMPTY (0 bytes): 2 issues.
        unlink($root . '/a.php');
        file_put_contents($root . '/sub/b.php', '');
        clearstatcache();
        $result = $check->get_result();
        $this->assertSame(result::ERROR, $result->get_status());
        $this->assertStringContainsString('2', $result->get_summary());
        $this->assertStringContainsString('a.php', $result->get_details());
        $this->assertStringContainsString('sub/b.php', $result->get_details());

        // Unreadable manifest → warning, never fatal.
        file_put_contents($root . '/integrity.json', '{rotisimo');
        $this->assertSame(result::WARNING, $check->get_result()->get_status());
    }
}
