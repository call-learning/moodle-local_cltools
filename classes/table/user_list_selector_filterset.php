<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Participants table filterset.
 *
 * @package    core
 * @category   table
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_cltools\table;

use core_table\local\filter\boolean_filter;
use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;
use mod_scorm\event\interactions_viewed;

/**
 * Participants table filterset.
 *
 * @package    core
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_list_selector_filterset extends filterset {
    /**
     * Get the required filters.
     *
     *
     * @return array.
     */
    public function get_required_filters(): array {
        return [];
    }

    /**
     * Get the optional filters.
     *
     * These are:
     * - accesssince;
     * - keywords;
     * - roles; and
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return [
            'courseid' => integer_filter::class, // This is to be able to use the participant filter js.
            'accesssince' => integer_filter::class,
            'keywords' => string_filter::class,
            'roles' => integer_filter::class
        ];
    }
}
