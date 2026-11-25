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
 * Edition of the plugin (Marketplace or website).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock\local;

/**
 * Edition of the plugin.
 *
 * The plugin is packaged in two editions from the same code. The edition
 * distributed through the Moodle Marketplace (the one kept in the repository)
 * shows the free features only. The edition downloaded from the author's
 * website also presents the features of the commercial suite. The only
 * difference between the two packages is the value of SUITE_URL, which the
 * packaging script fills in for the website edition.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edition {
    /** @var string Address of the suite presented by the website edition; empty in the Marketplace edition. */
    public const SUITE_URL = '';

    /** @var string Marketplace page of the full report plugin (the only link in the Marketplace edition). */
    public const MARKETPLACE_URL = 'https://marketplace.moodle.com/plugins/local_efreport';

    /**
     * Whether this is the website edition (the one that presents the commercial suite).
     *
     * @return bool
     */
    public static function is_web(): bool {
        return self::SUITE_URL !== '';
    }

    /**
     * Address the website edition links to (empty string in the Marketplace edition).
     *
     * @return string
     */
    public static function suite_url(): string {
        return self::SUITE_URL;
    }

    /**
     * Host name of the suite address, for display (empty string in the Marketplace edition).
     *
     * @return string
     */
    public static function suite_host(): string {
        return (string) parse_url(self::SUITE_URL, PHP_URL_HOST);
    }
}
