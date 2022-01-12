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
namespace local_cltools\local\filter;

use advanced_testcase;
use core_table\local\filter\filter;

defined('MOODLE_INTERNAL') || die;

/**
 * Helper class to deal with filters and filterset test case
 *
 * @package   local_cltools
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_helper_test extends advanced_testcase {
    /**
     * Map join types to corresponding SQL values
     *
     */
    public function test_get_jointype_to_sql_join() {
        $this->assertEquals('AND', trim(filter_helper::get_jointype_to_sql_join(filter::JOINTYPE_ALL)));
        $this->assertEquals('OR', trim(filter_helper::get_jointype_to_sql_join(filter::JOINTYPE_ANY)));
        $this->assertEquals('AND', trim(filter_helper::get_jointype_to_sql_join(filter::JOINTYPE_NONE)));
        $this->assertEquals('AND', trim(filter_helper::get_jointype_to_sql_join(50)));
    }

    /**
     * Return filter SQL joined but the filter type
     *
     */
    public function test_get_sql_filter_join() {
        $filter = new numeric_comparison_filter('numeric', filter::JOINTYPE_ANY);
        [$where] = filter_helper::get_sql_filter_join($filter, ['a', 'b', 'c'], [1, 2]);
        $this->assertEquals('(a OR b OR c)', $where);
    }

    /**
     * Return filter sanitized name (for use in SQL)
     *
     */
    public function test_get_sanitized_name() {
        $this->assertEquals('abcd', filter_helper::get_sanitized_name('ABCD '));
        $this->assertEquals('ab c', filter_helper::get_sanitized_name('AB c'));
        $this->assertEquals('abcd', filter_helper::get_sanitized_name('abcd'));
    }
}

