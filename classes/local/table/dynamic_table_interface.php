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
 * This is the pure dynamic table interface without the unnecessary methods from older moodle table (table_sql, flextable)
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\table;

use context;
use core_table\local\filter\filterset;
use local_cltools\local\filter\enhanced_filterset;

/**
 * This is the pure dynamic table interface without the unnecessary methods from older moodle table (table_sql, flextable)
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface dynamic_table_interface {
    /**
     * Sets the is_sortable variable to the given boolean, sort_default_column to
     * the given string, and the sort_default_order to the given integer.
     *
     * @param bool $bool
     * @param string|null $defaultcolumn
     * @param int $defaultorder
     * @return void
     */
    public function set_sortable(bool $bool, ?string $defaultcolumn, int $defaultorder = SORT_ASC): void;

    /**
     * Is the column sortable?
     *
     * @param string|null $column column name, null means table
     * @return bool
     */
    public function is_sortable(?string $column): bool;

    /**
     * Sets the use_pages variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function set_pageable(bool $bool): void;

    /**
     * Sets the pagesize variable to the given integer, the totalrows variable
     * to the given integer, and the use_pages variable to true.
     *
     * @param int $perpage
     * @param int $total
     * @return void
     */
    public function set_pagesize(int $perpage, int $total): void;

    /**
     * Is paged
     *
     * @return bool
     */
    public function is_pageable(): bool;

    /**
     * Total row
     *
     * @return int
     */
    public function get_total_rows(): int;

    /**
     * Get page start
     *
     * @return int the offset for LIMIT clause of SQL
     */
    public function get_page_start(): int;

    /**
     * Get page size
     *
     * @return int the pagesize for LIMIT clause of SQL
     */
    public function get_page_size(): int;

    /**
     * Get uniqueid for this table
     *
     * @return mixed
     */
    public function get_unique_id();

    /**
     * Set the list of hidden columns.
     *
     * @param array $columns The list of hidden columns.
     */
    public function set_hidden_columns(array $columns): void;

    /**
     * Set the preferred table sorting attributes.
     *
     * @param array $sortdata
     */
    public function set_sortdata(array $sortdata): void;

    /**
     * Set the page number.
     *
     * @param int $pagenumber The page number.
     */
    public function set_page_number(int $pagenumber): void;

    /**
     * Get the context for the table.
     *
     * Note: This function _must_ be overridden by dynamic tables to ensure that the context is correctly determined
     * from the filterset parameters.
     *
     * @return context
     */
    public function get_context(): context;

    /**
     * Set the filterset in the table class.
     *
     * The use of filtersets is a requirement for dynamic tables, but can be used by other tables too if desired.
     *
     * @param enhanced_filterset $filterset The filterset object to get filters and table parameters from
     */
    public function set_filterset(enhanced_filterset $filterset): void;

    /**
     * Get the currently defined filterset.
     *
     * @return filterset|null
     */
    public function get_filterset(): ?filterset;

    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     *
     * @param context $context
     * @param bool $writeaccess
     * @return bool
     */
    public static function validate_access(context $context, bool $writeaccess = false): bool;

}
