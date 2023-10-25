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

use core_table\local\filter\filter;

/**
 * Helper class to deal with filters and filterset
 *
 * @package    local_cltools
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @copyright  2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_helper {
    /**
     * Return filter SQL
     *
     * @param filter $filter
     * @param array $wheres
     * @param array $params
     * @return array array of two elements - SQL query and named parameters
     */
    public static function get_sql_filter_join(filter $filter, array $wheres, array $params): array {
        if (!empty($wheres)) {
            return ["("
                    . join(" " . static::get_jointype_to_sql_join($filter->get_join_type())
                            . " ", $wheres)
                    . ")",
                    $params, ];
        }
        return [];
    }

    /**
     * Map join types to corresponding SQL values
     *
     * @param int $jointtype
     * @return string
     */
    public static function get_jointype_to_sql_join(int $jointtype): string {
        $jointtypetosql = [
                filter::JOINTYPE_ALL => 'AND',
                filter::JOINTYPE_ANY => 'OR',
        ];
        return empty($jointtypetosql[$jointtype]) ? 'AND' : $jointtypetosql[$jointtype];
    }

    /**
     * Return filter sanitized name (for use in SQL)
     *
     * @param string $name
     * @return string
     */
    public static function get_sanitized_name(string $name): string {
        return trim(strtolower($name));
    }
}

