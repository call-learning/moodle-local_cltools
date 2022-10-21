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
 * Class representing an integer filter.
 *
 * @package    local_cltools
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @copyright  2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace local_cltools\local\filter;

use core_table\local\filter\filter;
use local_cltools\local\filter\adapter\enhanced_filter_adapter;
use local_cltools\local\filter\adapter\sql_adapter;

/**
 * Class representing an integer filter.
 *
 * @package    local_cltools
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @copyright  2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class numeric_comparison_filter extends \core_table\local\filter\numeric_comparison_filter
        implements sql_adapter, enhanced_filter_adapter {
    use enhanced_filter_impl;

    /**
     * Return filter SQL
     *
     * @param string $alias
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(string $alias): array {
        $wheres = [];
        $params = [];
        $sanitizedname = filter_helper::get_sanitized_name($this->get_name());
        $directiontosql = [
                '=' => '=',
                '==' => '=',
                '===' => '=',
                '>' => '>',
                '=>' => '>=',
                '<' => '<',
                '<=' => '<=',
        ];
        foreach ($this->get_filter_values() as $filterkey => $fieldval) {
            $paramname = "nump_{$sanitizedname}{$filterkey}";
            $direction = trim($fieldval->direction);
            $sqlcomparison = empty($directiontosql[$direction]) ? '=' : $directiontosql[$direction];
            $wheres[] = " COALESCE({$alias},0)  {$sqlcomparison}  :$paramname ";
            $params[$paramname] = intval($fieldval->value);
        }
        return filter_helper::get_sql_filter_join($this, $wheres, $params);
    }

    /**
     * Add a value to the filter. This differs from the basic class by the fact that we can
     * give an array instead of a json encoded value.
     *
     * @param string $value A json-encoded array or an array containing a direction, and comparison value
     * @return \core_table\local\filter\numeric_comparison_filter
     */
    public function add_filter_value($value): filter {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return parent::add_filter_value($value);
    }
}
