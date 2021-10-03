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

namespace local_cltools\local\table;
defined('MOODLE_INTERNAL') || die;

use context;
use core_table\local\filter\filterset;
use local_cltools\local\filter\enhanced_filterset;
use moodle_url;
use table_default_export_format_parent;

/**
 * This is the pure dynamic table interface without the unnecessary methods from older moodle table (table_sql, flextable)
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface dynamic_table_interface {
    /**
     * Call this to pass the download type. Use :
     *         $download = optional_param('download', '', PARAM_ALPHA);
     * To get the download type. We assume that if you call this function with
     * params that this table's data is downloadable, so we call is_downloadable
     * for you (even if the param is '', which means no download this time.
     * Also you can call this method with no params to get the current set
     * download type.
     *
     * @param string $download dataformat type. One of csv, xhtml, ods, etc
     * @param string $filename filename for downloads without file extension.
     * @param string $sheettitle title for downloaded data.
     * @return string download dataformat type. One of csv, xhtml, ods, etc
     */
    public function is_downloading($download = null, $filename = '', $sheettitle = '');

    /**
     * Get, and optionally set, the export class.
     *
     * @param $exportclass (optional) if passed, set the table to use this export class.
     * @return table_default_export_format_parent the export class in use (after any set).
     */
    public function export_class_instance($exportclass = null): table_default_export_format_parent;

    /**
     * Probably don't need to call this directly. Calling is_downloading with a
     * param automatically sets table as downloadable.
     *
     * @param bool $downloadable optional param to set whether data from
     * table is downloadable. If ommitted this function can be used to get
     * current state of table.
     * @return bool whether table data is set to be downloadable.
     */
    public function is_downloadable($downloadable = null);

    /**
     * Sets the is_sortable variable to the given boolean, sort_default_column to
     * the given string, and the sort_default_order to the given integer.
     *
     * @param bool $bool
     * @param string $defaultcolumn
     * @param int $defaultorder
     * @return void
     */
    public function sortable($bool, $defaultcolumn = null, $defaultorder = SORT_ASC);


    /**
     * Is the column sortable?
     *
     * @param string column name, null means table
     * @return bool
     */
    public function is_sortable($column = null);

    /**
     * Sets the is_collapsible variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function collapsible($bool);

    /**
     * Sets the use_pages variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function pageable($bool);

    /**
     * Sets the use_initials variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function initialbars($bool);

    /**
     * Sets the pagesize variable to the given integer, the totalrows variable
     * to the given integer, and the use_pages variable to true.
     *
     * @param int $perpage
     * @param int $total
     * @return void
     */
    public function pagesize($perpage, $total);

    /**
     * Sets $this->baseurl.
     *
     * @param moodle_url|string $url the url with params needed to call up this page
     */
    public function define_baseurl($url);

    /**
     * Get base URL
     *
     */
    public function get_baseurl();

    /**
     * Is paged
     * @return bool
     */
    public function is_pageable(): bool;

    /**
     * Total row
     * @return int
     */
    public function get_total_rows(): int;

    /**
     * Mark the table preferences to be reset.
     */
    public function mark_table_to_reset(): void;

    /**
     * @return int the offset for LIMIT clause of SQL
     */
    public function get_page_start();

    /**
     * @return int the pagesize for LIMIT clause of SQL
     */
    public function get_page_size();

    /**
     * Get uniqueid for this table
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
     * @param string $sortby The field to sort by.
     * @param int $sortorder The sort order.
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
     *
     * @throws \dml_exception
     */
    public function validate_access($writeaccess = false);

}
