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
 * Integer filter.
 *
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_cltools\local\filter;
defined('MOODLE_INTERNAL') || die;
use TypeError;

/**
 * Class representing an integer filter.
 *
 * @package    core
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class integer_filter extends filter {
    /**
     * Add a value to the filter.
     *
     * @param int $values
     * @return self
     */
    public function add_filter_value($value): parent {
        if (!is_int($value)) {
            $type = gettype($value);
            if ($type === 'object') {
                $type = get_class($value);
            }

            throw new TypeError("The value supplied was of type '{$type}'. An integer was expected.");
        }

        if (array_search($value, $this->filtervalues) !== false) {
            // Remove duplicates.
            return $this;
        }

        $this->filtervalues[] = $value;

        return $this;
    }

    /**
     * Get a specific filter for an element
     *
     * @param $fieldval
     * @param string $joinsql
     * @param null $tableprefix
     * @return array
     */
    protected function get_sql_filter_element($fieldval, $tableprefix = null) {
        static $paramcount = 0;

        $paramname = "intergerp_". ($paramcount++);
        $params = [];
        $where = " {$this->get_alias()}  =  :$paramname ";
        $params[$paramname] = intval($fieldval->value);
        return array($where, $params);
    }
}
