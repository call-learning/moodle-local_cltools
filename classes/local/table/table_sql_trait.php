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

use coding_exception;
use context;
use core\dml\recordset_walk;
use core_table\dynamic;
use html_writer;
use moodle_recordset;
use moodle_url;
use stdClass;
use table_dataformat_export_format;
use table_default_export_format_parent;

defined('MOODLE_INTERNAL') || die;

/**
 * This trait fills the gap between table_sql and the dynamic table implementation
 *
 * This helps to make dynamic_table a drop in replacement for table_sql via duck-typing
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait table_sql_trait {

    protected bool $issortable;
    protected $sortdefaultcolumn;
    protected $sortdefaultorder;
    protected $columnnosort;
    protected $iscollapsible;
    protected $usepages;
    protected $useinitials;
    protected $pagesize;
    protected $totalrows;
    protected $baseurl;
    protected $columns;
    protected $headers;
    protected $resetting;
    protected $currpage;
    protected $issetup;
    protected array $rawdata = [];
    protected string $uniqueid;
    protected $userfieldid;

    protected $download;
    protected $countsql;
    protected $sql;

    /**
     * Sets the issortable variable to the given boolean, sort_default_column to
     * the given string, and the sort_default_order to the given integer.
     *
     * @param bool $bool
     * @param string $defaultcolumn
     * @param int $defaultorder
     * @return void
     */
    public function sortable($bool, $defaultcolumn = null, $defaultorder = SORT_ASC) {
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
     * Sets the is_collapsible variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function collapsible($bool) {
        $this->iscollapsible = $bool;
    }

    /**
     * Sets the use_pages variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function pageable($bool) {
        $this->usepages = $bool;
    }

    /**
     * Sets the use_initials variable to the given boolean.
     *
     * @param bool $bool
     * @return void
     */
    public function initialbars($bool) {
        $this->useinitials = $bool;
    }


    function pagesize($perpage, $total) {
        if ($this->usepages) {
            $this->pagesize = $perpage;
            $this->totalrows = $total;
            $this->usepages = true;
        }
    }

    /**
     * Sets $this->baseurl.
     *
     * @param moodle_url|string $url the url with params needed to call up this page
     */
    public function define_baseurl($url) {
        $this->baseurl = new moodle_url($url);
    }

    /**
     * Get base URL
     *
     */
    public function get_baseurl() {
        return $this->baseurl;
    }

    /**
     * Sets the pagesize variable to the given integer, the totalrows variable
     * to the given integer, and the use_pages variable to true.
     *
     * @param int $perpage
     * @param int $total
     * @return void
     */
    // phpcs:ignore Squiz.Scope.MethodScope.Missing
    /**
     * Mark the table preferences to be reset.
     */
    public function mark_table_to_reset(): void {
        $this->resetting = true;
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
     * Get the html for the download buttons
     *
     * Usually only use internally
     */
    public function download_buttons() {

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
        if (empty($this->baseurl)) {
            debugging('You should set baseurl when using flexible_table.');
            global $PAGE;
            $this->baseurl = $PAGE->url;
        }

        if ($this->currpage == null) {
            $this->currpage = optional_param($this->get_request_var_name('currpage'), 0, PARAM_INT);
        }

        $this->issetup = true;

        if ($this->usepages) {
            $this->currpage = $currpage ? $currpage : $this->currpage;
        }
        return $this->issetup;
    }

    protected function get_request_var_name($requestvarname) {
        return $requestvarname . md5($this->get_unique_id());
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
     * @return array sql to add to where statement and params.
     */
    protected function get_sql_where() {
        global $DB;

        $conditions = array();
        $params = array();

        if (isset($this->columns['fullname'])) {
            static $i = 0;
            $i++;

            if (!empty($this->prefs['i_first'])) {
                $conditions[] = $DB->sql_like('firstname', ':ifirstc' . $i, false, false);
                $params['ifirstc' . $i] = $this->prefs['i_first'] . '%';
            }
            if (!empty($this->prefs['i_last'])) {
                $conditions[] = $DB->sql_like('lastname', ':ilastc' . $i, false, false);
                $params['ilastc' . $i] = $this->prefs['i_last'] . '%';
            }
        }

        return [
                implode(" AND ", $conditions),
                $params,
        ];
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
        if (isset($row->$column) && ($column === 'email' || $column === 'idnumber') &&
                (!$this->is_downloading() || $this->export_class_instance()->supports_html())) {
            // Columns email and idnumber may potentially contain malicious characters, escape them by default.
            // This function will not be executed if the child class implements col_email() or col_idnumber().
            return s($row->$column);
        }
        return null;
    }

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
    public function is_downloading($download = null, $filename = '', $sheettitle = '') {
        if ($download !== null) {
            $this->sheettitle = $sheettitle;
            $this->is_downloadable(true);
            $this->download = $download;
            $this->filename = clean_filename($filename);
            $this->export_class_instance();
        }
        return $this->download;
    }

    /**
     * Probably don't need to call this directly. Calling is_downloading with a
     * param automatically sets table as downloadable.
     *
     * @param bool $downloadable optional param to set whether data from
     * table is downloadable. If ommitted this function can be used to get
     * current state of table.
     * @return bool whether table data is set to be downloadable.
     */
    public function is_downloadable($downloadable = null) {
        if ($downloadable !== null) {
            $this->downloadable = $downloadable;
        }
        return $this->downloadable;
    }

    /**
     * Get, and optionally set, the export class.
     *
     * @param $exportclass (optional) if passed, set the table to use this export class.
     * @return table_default_export_format_parent the export class in use (after any set).
     */
    public function export_class_instance($exportclass = null): table_default_export_format_parent {
        if (!is_null($exportclass)) {
            $this->started_output = true;
            $this->exportclass = $exportclass;
            $this->exportclass->table = $this;
        } else if (is_null($this->exportclass) && !empty($this->download)) {
            $this->exportclass = new table_dataformat_export_format($this, $this->download);
            if (!$this->exportclass->document_started()) {
                $this->exportclass->start_document($this->filename, $this->sheettitle);
            }
        }
        return $this->exportclass;
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
        if ($this->download) {
            return $name;
        }

        $userid = $row->{$this->useridfield};
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
        if (!$this->is_downloading()) {
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
        } else {
            $eci = $this->export_class_instance();
            return $eci->format_text($text, $format, $options, $courseid);
        }
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

    /**
     * This is only needed if you want to use different sql to count rows.
     * Used for example when perhaps all db JOINS are not needed when counting
     * records. You don't need to call this function the count_sql
     * will be generated automatically.
     *
     * We need to count rows returned by the db seperately to the query itself
     * as we need to know how many pages of data we have to display.
     */
    protected function set_count_sql($sql, array $params = null) {
        $this->countsql = $sql;
        $this->countparams = $params;
    }

    /**
     * Set the sql to query the db. Query will be :
     *      SELECT $fields FROM $from WHERE $where
     * Of course you can use sub-queries, JOINS etc. by putting them in the
     * appropriate clause of the query.
     */
    protected function set_sql($fields, $from, $where, array $params = array()) {
        $this->sql = new stdClass();
        $this->sql->fields = $fields;
        $this->sql->from = $from;
        $this->sql->where = empty($where) ? ' 1=1 ' : $where;
        $this->sql->params = $params;
    }

    /**
     * Get the columns to sort by, in the form required by {@link construct_order_by()}.
     *
     * @return array column name => SORT_... constant.
     */
    protected function get_sort_columns() {
        if (!$this->issetup) {
            throw new coding_exception('Cannot call get_sort_columns until you have called setup.');
        }

        if (empty($this->prefs['sortby'])) {
            return array();
        }

        foreach ($this->prefs['sortby'] as $column => $notused) {
            if (isset($this->columns[$column])) {
                continue; // This column is OK.
            }
            if (in_array($column, get_all_user_name_fields()) &&
                    isset($this->columns['fullname'])) {
                continue; // This column is OK.
            }
            // This column is not OK.
            unset($this->prefs['sortby'][$column]);
        }

        return $this->prefs['sortby'];
    }
}
