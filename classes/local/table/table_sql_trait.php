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
 * This helps to make dynamic_table a drop in replacement for table_sql via duck-typing
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_cltools\local\table;

use coding_exception;
use context;
use core\dml\recordset_walk;
use core_table\dynamic;
use html_writer;
use moodle_recordset;
use moodle_url;
use stdClass;

/**
 * This traits fill part of the gap between table_sql but implementation is progressively distancing itself from
 * the original implementation
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait table_sql_trait {

    protected $issortable;
    protected $sortdefaultcolumn;
    protected $sortdefaultorder;
    protected $columnnosort;
    protected $iscollapsible;
    protected $usepages;
    protected $pagesize;
    protected $totalrows;
    protected $columns;
    protected $headers;
    protected $currpage;
    protected $issetup;
    protected $rawdata = [];
    protected $uniqueid;
    protected $userfieldid;

    /**
     * Sets the issortable variable to the given boolean, sort_default_column to
     * the given string, and the sort_default_order to the given integer.
     *
     * @param bool $bool
     * @param string $defaultcolumn
     * @param int $defaultorder
     * @return void
     */
    public function set_sortable($bool, $defaultcolumn = null, $defaultorder = SORT_ASC) {
        $this->issortable = $bool;
        $this->sortdefaultcolumn = $defaultcolumn;
        $this->sortdefaultorder = $defaultorder;
    }

    /**
     * Is the column sortable?
     *
     * @param string column name, null means table
     * @return bool
     */
    public function is_sortable($column = null) {
        if (empty($column)) {
            return $this->issortable;
        }
        if (!$this->issortable) {
            return false;
        }
        return !in_array($column, $this->columnnosort);
    }

    /**
     * Sets the use_pages variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function set_pageable($bool) {
        $this->usepages = $bool;
    }

    /**
     * Set page size
     *
     * @param int $perpage
     * @param int $total
     * @return void
     */
    public function set_pagesize($perpage, $total) {
        if ($this->usepages) {
            $this->pagesize = $perpage;
            $this->totalrows = $total;
            $this->usepages = true;
        }
    }

    /**
     * @return int the offset for LIMIT clause of SQL
     */
    public function get_page_start() {
        if (!$this->usepages) {
            return '';
        }
        return $this->currpage * $this->pagesize;
    }

    /**
     * @return int the pagesize for LIMIT clause of SQL
     */
    public function get_page_size() {
        if (!$this->usepages) {
            return '';
        }
        return $this->pagesize;
    }


    /**
     * Set the list of hidden columns.
     *
     * @param array $columns The list of hidden columns.
     */
    public function set_hidden_columns(array $columns): void {
        $this->hiddencolumns = $columns;
    }

    /**
     * Set the page number.
     *
     * @param int $pagenumber The page number.
     */
    public function set_page_number(int $pagenumber): void {
        $this->currpage = $pagenumber - 1;
    }

    public function is_pageable(): bool {
        return $this->usepages;
    }

    public function get_total_rows(): int {
        return $this->totalrows ?? 0;
    }

    /**
     * @param array $columns an array of identifying names for columns. If
     * columns are sorted then column names must correspond to a field in sql.
     */
    protected function define_columns($columns) {
        $this->columns = array();
        $colnum = 0;

        foreach ($columns as $column) {
            $this->columns[$column] = $colnum++;
        }
    }

    /**
     * @param array $headers numerical keyed array of displayed string titles
     * for each column.
     */
    protected function define_headers($headers) {
        $this->headers = $headers;
    }

    /**
     * Must be called after table is defined. Use methods above first. Cannot
     * use functions below till after calling this method.
     *
     * @return bool
     */
    protected function setup() {
        if ($this->usepages) {
            $currpage = $this->currpage;
        }
        if (empty($this->columns) || empty($this->uniqueid)) {
            return false;
        }
        $this->issetup = true;

        if ($this->usepages) {
            $this->currpage = $currpage ? $currpage : $this->currpage;
        }
        return $this->issetup;
    }


    /**
     * Get uniqueid for this table
     *
     * @return mixed
     */
    public function get_unique_id() {
        return $this->uniqueid;
    }

    /**
     * Prepare an an order by clause from the list of columns to be sorted.
     *
     * @param array $cols column name => SORT_ASC or SORT_DESC
     * @return string SQL fragment that can be used in an ORDER BY clause.
     */
    protected function construct_order_by($cols, $textsortcols = array()) {
        global $DB;
        $bits = array();

        foreach ($cols as $column => $order) {
            if (in_array($column, $textsortcols)) {
                $column = $DB->sql_order_by_text($column);
            }
            if ($order == SORT_ASC) {
                $bits[] = $column . ' ASC';
            } else {
                $bits[] = $column . ' DESC';
            }
        }

        return implode(', ', $bits);
    }

    /**
     * Call appropriate methods on this table class to perform any processing on values before displaying in table.
     * Takes raw data from the database and process it into human readable format, perhaps also adding html linking when
     * displaying table as html, adding a div wrap, etc.
     *
     * See for example col_fullname below which will be called for a column whose name is 'fullname'.
     *
     * @param array|object $row row of data from db used to make one row of the table.
     * @return array one row for the table
     */
    protected function format_row($row) {
        if (is_array($row)) {
            $row = (object) $row;
        }
        $formattedrow = array();
        foreach (array_keys($this->columns) as $column) {
            $colmethodname = 'col_' . $column;
            if (method_exists($this, $colmethodname)) {
                $formattedcolumn = $this->$colmethodname($row);
            } else {
                $formattedcolumn = $this->other_cols($column, $row);
                if ($formattedcolumn === null) {
                    $formattedcolumn = $row->$column;
                }
            }
            $formattedrow[$column] = $formattedcolumn;
        }
        return $formattedrow;
    }

    /**
     * You can override this method in a child class. See the description of
     * build_table which calls this method.
     */
    protected function other_cols($column, $row) {
        if (isset($row->$column) && ($column === 'email' || $column === 'idnumber')) {
            // Columns email and idnumber may potentially contain malicious characters, escape them by default.
            // This function will not be executed if the child class implements col_email() or col_idnumber().
            return s($row->$column);
        }
        return null;
    }

    /**
     * Fullname is treated as a special columname in tablelib and should always
     * be treated the same as the fullname of a user.
     *
     * @param object $row the data from the db containing all fields from the
     *                    users table necessary to construct the full name of the user in
     *                    current language.
     * @return string contents of cell in column 'fullname', for this row.
     * @uses $this->useridfield if the userid field is not expected to be id
     * then you need to override $this->useridfield to point at the correct
     * field for the user id.
     *
     */
    protected function col_fullname($row) {
        global $COURSE;

        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->get_context()));

        if (!empty($this->useridfield)) {
            $userid = $row->{$this->useridfield};
        } else {
            $userid = $row->id;
        }
        if ($COURSE->id == SITEID) {
            $profileurl = new moodle_url('/user/profile.php', array('id' => $userid));
        } else {
            $profileurl = new moodle_url('/user/view.php',
                    array('id' => $userid, 'course' => $COURSE->id));
        }
        return html_writer::link($profileurl, $name);
    }

    /**
     * Get the context for the table.
     *
     * Note: This function _must_ be overridden by dynamic tables to ensure that the context is correctly determined
     * from the filterset parameters.
     *
     * @return context
     */
    public function get_context(): context {
        global $PAGE;

        if (is_a($this, dynamic::class)) {
            throw new coding_exception('The get_context function must be defined for a dynamic table');
        }

        return $PAGE->context;
    }

    /**
     * Used from col_* functions when text is to be displayed. Does the
     * right thing - either converts text to html or strips any html tags
     * depending on if we are downloading and what is the download type. Params
     * are the same as format_text function in weblib.php but some default
     * options are changed.
     */
    protected function format_text($text, $format = FORMAT_MOODLE, $options = null, $courseid = null) {
        if (is_null($options)) {
            $options = new stdClass;
        }
        if (!isset($options->para)) {
            $options->para = false;
        }
        if (!isset($options->newlines)) {
            $options->newlines = false;
        }
        if (!isset($options->smiley)) {
            $options->smiley = false;
        }
        if (!isset($options->filter)) {
            $options->filter = false;
        }
        return format_text($text, $format, $options);
    }

    /**
     * Closes recordset (for use after building the table).
     */
    protected function close_recordset() {
        if ($this->rawdata && ($this->rawdata instanceof recordset_walk ||
                        $this->rawdata instanceof moodle_recordset)) {
            $this->rawdata->close();
            $this->rawdata = [];
        }
    }
}
