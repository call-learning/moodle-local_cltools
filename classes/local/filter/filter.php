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
 * Generic filterset
 *
 * This is a backport of 3.9 table/filter with the idea of generalising this to all
 * types of SQL filters. For now this will be used by tabulator.js implementatio
 *
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

declare(strict_types=1);

namespace local_cltools\local\filter;
defined('MOODLE_INTERNAL') || die;

use Countable;
use JsonSerializable;
use InvalidArgumentException;
use Iterator;

/**
 * Class representing a generic filter of any type.
 *
 * @package    core
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class filter implements Countable, JsonSerializable {

    /** @var int Any of the following match */
    const JOINTYPE_ANY = 1;

    /** @var int All of the following match */
    const JOINTYPE_ALL = 2;
    const JOIN_TYPES = [
        self::JOINTYPE_ANY,
        self::JOINTYPE_ALL
    ];
    /**
     * Map join types to corresponding SQL values
     *
     */
    const JOIN_TYPE_TO_SQL = [
        self::JOINTYPE_ALL => 'AND',
        self::JOINTYPE_ANY => 'OR',
    ];
    /** @var string The name of this filter */
    protected $name = null;
    /** @var string The sql alias of this filter */
    protected $alias = null;
    /** @var int The join type currently in use */
    protected $jointype = self::JOINTYPE_ALL;
    /** @var array The list of active filter values */
    protected $filtervalues = [];

    /**
     * Constructor for the generic filter class.
     *
     * @param string $name The name of the current filter.
     * @param int $jointype The join to use when combining the filters.
     *                      See the JOINTYPE_ constants for further information on the field.
     * @param mixed[] $values An array of filter objects to be applied.
     * @param string $alias An alias for the column, which can be used directly in the related
     * sql query.
     */
    public function __construct(string $name,
        ?int $jointype = null,
        ?array $values = null,
        ?string $alias = null
    ) {
        $this->name = $name;
        $this->alias = $alias;
        if ($jointype !== null) {
            $this->set_join_type($jointype);
        }

        if (!empty($values)) {
            foreach ($values as $value) {
                $this->add_filter_value($value);
            }
        }
    }

    /**
     * Add a value to the filter.
     *
     * @param mixed $value
     * @return self
     */
    public function add_filter_value($value): self {
        if ($value === null) {
            // Null values are usually invalid.
            return $this;
        }

        if ($value === '') {
            // Empty strings are invalid.
            return $this;
        }

        if (array_search($value, $this->filtervalues) !== false) {
            // Remove duplicates.
            return $this;
        }

        $this->filtervalues[] = $value;

        return $this;
    }

    /**
     * Return the number of values.
     *
     * @return int
     */
    public function count(): int {
        return count($this->filtervalues);
    }

    /**
     * Return the alias of the filter, mainly for sql queries
     *
     * @return string either the set alias or the name of the column.
     */
    public function get_alias(): string {
        if (empty($this->alias)) {
            return $this->name;
        }
        return $this->alias;
    }

    /**
     * Set filter alias
     */
    public function set_alias($alias) {
        $this->alias = $alias;
    }

    /**
     * Serialize filter.
     *
     * @return mixed|object
     */
    public function jsonSerialize() {
        $currentclass = explode("\\", static::class);
        $serialised = (object) [
            'name' => $this->get_name(),
            'jointype' => $this->get_join_type(),
            'values' => array_map(function($val) {
                return json_encode($val);
            }, $this->get_filter_values()),
            'type' => end($currentclass)
        ];
        return $serialised;
    }

    /**
     * Return the name of the filter.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Return the currently specified join type.
     *
     * @return int
     */
    public function get_join_type(): int {
        return $this->jointype;
    }

    /**
     * Specify the type of join to employ for the filter.
     *
     * @param int $jointype The join type to use using one of the supplied constants
     * @return self
     */
    public function set_join_type(int $jointype): self {
        if (array_search($jointype, static::JOIN_TYPES) === false) {
            throw new InvalidArgumentException('Invalid join type specified');
        }

        $this->jointype = $jointype;

        return $this;
    }

    /**
     * Return the current filter values.
     *
     * @return mixed[]
     */
    public function get_filter_values(): array {
        $this->sort_filter_values();
        return $this->filtervalues;
    }

    /**
     * Sort the filter values to ensure reliable, and consistent output.
     */
    protected function sort_filter_values(): void {
        // Sort the filter values to ensure consistent output.
        // Note: This is not a locale-aware sort, but we don't need this.
        // It's primarily for consistency, not for actual sorting.
        sort($this->filtervalues);
    }

    /**
     * Get the sql where / params used for filtering
     *
     * @param $tableprefix
     * @return array
     */
    public function get_sql_for_filter($tableprefix = null) {
        $filtervalues = $this->get_filter_values();
        $joinsql = self::JOIN_TYPE_TO_SQL[$this->get_join_type()];
        $filterwheres = [];
        $filterparams = [];
        foreach ($filtervalues as $fieldval) {
            list($wheres, $params) = $this->get_sql_filter_element($fieldval, $tableprefix);
            $filterwheres[] = $wheres;
            $filterparams += $params;
        }
        return array("(". join(" $joinsql ", $filterwheres). ")", $filterparams);
    }

    /**
     * Get a specific filter for an element that can be joined later
     *
     * @param $fieldval
     * @param string $joinsql
     * @param null $tableprefix
     * @return array
     */
    abstract protected function get_sql_filter_element($fieldval, $tableprefix = null);
}
