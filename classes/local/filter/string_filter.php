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
 * String filter.
 *
 * @copyright  2020 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_cltools\local\filter;
defined('MOODLE_INTERNAL') || die;

use local_cltools\local\filter\adapter\sql_adapter;
use TypeError;

/**
 * Class representing a string filter.
 *
 * @package    core
 * @copyright  2020 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class string_filter extends \core_table\local\filter\string_filter implements sql_adapter{
    /**
     * Return filter SQL
     *
     * @param string $columnname
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(string $columnname) : array{
        global $DB;
        $wheres = [];
        $params = [];
        $sanitizedname = filter_helper::get_sanitized_name($this->get_name());
        foreach($this->get_filter_values() as $filterkey => $fieldval) {
            $fieldval = trim($fieldval, '"');// Remove double quote if any.
            $paramname = "strp_{$sanitizedname}{$filterkey}";
            $wheres[] = " {$DB->sql_like($columnname, ':'.$paramname, false, false)} ";
            $params[$paramname] = "%{$DB->sql_like_escape($fieldval)}%";
        }
        return filter_helper::get_sql_filter_join($this, $wheres, $params);
    }

}
