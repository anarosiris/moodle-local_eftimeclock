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
 * Tests for the edition of local_eftimeclock.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * The code in the repository is the Marketplace edition.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(edition::class)]
final class edition_test extends \advanced_testcase {
    /**
     * The repository holds the Marketplace edition: no suite address, so nothing of the suite is shown.
     */
    public function test_repository_is_the_marketplace_edition(): void {
        $this->assertSame('', edition::SUITE_URL, 'The repository must hold the Marketplace edition.');
        $this->assertFalse(edition::is_web());
        $this->assertSame('', edition::suite_url());
        $this->assertSame('', edition::suite_host());
        $this->assertStringStartsWith('https://marketplace.moodle.com/plugins/', edition::MARKETPLACE_URL);
    }
}
