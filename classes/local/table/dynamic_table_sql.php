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
 * Dynamic table addons
 *
 * This is basically for Moodle 3.9 the similar to 'extends \table_sql implements dynamic_table'
 * but with the capability to find the core table in persistent namespace
 * This does not inherit from table_sql anymore and a fork of the original concept.
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\table;
defined('MOODLE_INTERNAL') || die;

use coding_exception;
use context_system;
use core_table\local\filter\filterset;
use dml_exception;
use local_cltools\local\field\persistent_field;
use local_cltools\local\filter\enhanced_filterset;
use local_cltools\local\table\external\helper;

abstract class dynamic_table_sql implements dynamic_table_interface {
    use table_sql_trait;
    /**
     * @var bool $iseditable is table editable ?
     */
    protected $iseditable = false;
    /**
     * @var enhanced_filterset The currently applied filerset
     * This is required for dynamic tables, but can be used by other tables too if desired.
     */
    protected $filterset = null;
    /**
     * @var array field defintions
     */
    protected $fields = [];
    /**
     * @var array|mixed|null defined actions
     */
    protected $actionsdefs = [];
    /**
     * @var array $sortfieldaliases an associative array that will set the right
     *  sql alias for this table if needed (sorting)
     */
    protected $sortfieldaliases = [];
    /**
     * @var array $fieldaliases an associative array that will set the right
     *  sql alias for this table if needed (filters)
     */
    protected $fieldaliases = [];

    private string $sheettitle;

    /**
     * Sets up the page_table parameters.
     *
     * @throws coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid = null,
        $actionsdefs = null,
        $editable = false
    ) {
        $this->uniqueid = $uniqueid ? $uniqueid : \html_writer::random_id('dynamictable');
        $this->actionsdefs = $actionsdefs;
        $this->iseditable = (bool) $editable;
        list($cols, $headers) = $this->get_table_columns_definitions();
        $this->define_columns($cols);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->sql = (object) [
            'where' => '',
            'from' => '',
            'params' => [],
            'sort' => ''
        ];
        $this->set_initial_sql();
    }

    /**
     * Table columns
     *
     * @return array[]
     * @throws coding_exception
     */
    protected function get_table_columns_definitions() {
        // Create the related persistent filter form.
        $cols = [];
        $headers = [];

        $this->setup_fields();
        foreach ($this->fields as $field) {
            $cols[] = $field->get_name();
            $headers[] = $field->get_display_name();
        }
        if (!in_array('id', $cols)) {
            $cols[] = 'id';
        }
        $headers[] = get_string('id', 'local_cltools');
        return [$cols, $headers];
    }

    /**
     * Setup the fields for this table
     */
    abstract protected function setup_fields();

    protected function set_initial_sql() {
        // Empty in this class but used in subclasses.
    }

    /**
     * Set the filterset in the table class.
     * If there was an existing filter replaces them by the new definition.
     *
     * @param enhanced_filterset $filterset The filterset object to get filters and table parameters from
     */
    public function set_filterset(enhanced_filterset $filterset): void {
        // If there existing filters we replace them.
        if ($this->filterset) {
            foreach($filterset as $filter) {
                $this->filterset->add_filter($filter);
            }
        } else {
            $this->filterset = $filterset;
        }
    }

    /**
     * Get the currently defined filterset.
     *
     * @return \local_cltools\local\table\filterset|null
     */
    public function get_filterset(): ?filterset {
        return $this->filterset;
    }

    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     *
     *
     * @throws dml_exception
     */
    public function validate_access($writeaccess = false) {
        helper::validate_context(context_system::instance());
    }

    /**
     * Retrieve data from the database and return a row set
     *
     * @return array
     */
    public function retrieve_raw_data($pagesize) {
        $rows = [];
        if ($this->setup()) {
            $this->query_db($pagesize, false);
            foreach ($this->rawdata as $row) {
                $formattedrow = $this->format_row($row);
                $rows[] = (object) $formattedrow;
            }
            $this->close_recordset();
        }
        return $rows;
    }

    /**
     * Set the preferred table sorting attributes.
     *
     * This is a modified version.
     *
     * @param string $sortby The field to sort by.
     * @param int $sortorder The sort order.
     */
    public function set_sortdata(array $sortdata): void {
        $this->sortdata = [];
        foreach ($sortdata as $sortitem) {
            if (!array_key_exists($sortitem['sortby'], $this->sortdata)) {
                if (is_numeric($sortitem['sortorder'])) {
                    $sortorder =(int) $sortitem['sortorder'];
                } else {
                    $sortorder = ($sortitem['sortorder'] === 'ASC') ? SORT_ASC : SORT_DESC;
                }
                $this->sortdata[$sortitem['sortby']] = $sortorder;
            }
        }
    }

    /**
     * Main method to create the underlying query (SQL)
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param bool $disablefilters disable filters
     */
    public function query_db($pagesize, $useinitialsbar = true, $disablefilters = false) {
        $additionalwhere = null;
        $additionalparams = [];
        if (!empty($this->filterset) && !$disablefilters) {
            list($additionalwhere, $additionalparams) = $this->filterset->get_sql_for_filter(null, null, $this->fieldaliases);
        }
        if ($additionalwhere) {
            if (!empty($this->sql->where)) {
                $this->sql->where .= " AND ($additionalwhere)";
            } else {
                $this->sql->where = "$additionalwhere";
            }
            $this->sql->params += $additionalparams;
        }
        $this->sql->fields = "DISTINCT " . $this->sql->fields;
        if ($this->countsql === null) {
            $this->countsql = "SELECT COUNT(1) FROM (SELECT {$this->sql->fields}
            FROM {$this->sql->from}
            WHERE {$this->sql->where}) squery";
            $this->countparams = $this->sql->params;
        }

        global $DB;
        if (!$this->is_downloading()) {
            if ($this->countsql === null) {
                $this->countsql = 'SELECT COUNT(1) FROM ' . $this->sql->from . ' WHERE ' . $this->sql->where;
                $this->countparams = $this->sql->params;
            }
            $grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
            if ($useinitialsbar && !$this->is_downloading()) {
                $this->initialbars(true);
            }

            list($wsql, $wparams) = $this->get_sql_where();
            if ($wsql) {
                $this->countsql .= ' AND ' . $wsql;
                $this->countparams = array_merge($this->countparams, $wparams);

                $this->sql->where .= ' AND ' . $wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);

                $total = $DB->count_records_sql($this->countsql, $this->countparams);
            } else {
                $total = $grandtotal;
            }

            $this->pagesize($pagesize, $total);
        }

        // Fetch the attempts.
        $sort = $this->construct_order_by($this->get_sort_columns());
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$sort}";

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }

    }

    /**
     * @return array
     */
    public function get_fields_definition() {
        $columnsdef = [];
        $this->setup();
        foreach ($this->columns as $fieldshortname => $index) {
            if (empty($this->fields[$index])) {
                $column = (object) [
                    'title' => '',
                    'field' => $fieldshortname,
                    'visible' => false,
                ];
            } else {
                $field = $this->fields[$index];
                $column = (object) [
                    'title' => $this->headers[$index],
                    'field' => $fieldshortname,
                    'visible' => $field->is_visible(),
                ];
                // Add formatter, filter, editor...
                /* @var persistent_field $field field */
                foreach (['formatter', 'filter', 'editor', 'validator'] as $modifier) {
                    $callback = "get_column_$modifier";
                    $modifiervalues = (array) $field->$callback();
                    if ($field->is_visible() && $modifiervalues) {
                        if (in_array($modifier, ['editor', 'validator']) && !$this->iseditable) {
                            continue;
                        }
                        foreach ($modifiervalues as $modifiername => $value) {
                            if (is_object($value) || is_array($value)) {
                                $value = json_encode($value);
                            }
                            $column->$modifiername = $value;
                        }
                    }
                }
                $colmethodname = 'col_' . $fieldshortname;
                // Disable sorting and formatting for all formatted rows
                // Except for row which are html formatted (in which case we just disable the sorting).
                if (method_exists($this, $colmethodname)) {
                    if (empty($this->fields[$fieldshortname . 'format'])) {
                        unset($column->filter);
                        unset($column->filterparams);
                    }
                    $column->formatter = 'html';
                    unset($column->formatterparams);
                }
            }
            $columnsdef[$fieldshortname] = $column;
        }

        return $columnsdef;
    }

    /**
     * Set the value of a specific row.
     *
     * @param $rowid
     * @param $fieldname
     * @param $newvalue
     * @param $oldvalue
     * @return bool
     */
    public function set_value($rowid, $fieldname, $newvalue, $oldvalue) {
        return false;
    }

    /**
     * Check if the value is valid for this row, column
     *
     * @param $rowid
     * @param $fieldname
     * @param $newvalue
     * @return bool
     */
    public function is_valid_value($rowid, $fieldname, $newvalue) {
        return false;
    }

    /**
     * Check if table editable
     *
     * @returns bool
     */
    public function is_editable() {
        return $this->iseditable;
    }

    /**
     * @throws coding_exception
     */
    protected function setup_other_fields() {
        if ($this->actionsdefs) {
            $this->fields['actions'] = persistent_field::get_instance_from_def('html', [
                'fullname' => get_string('actions', 'local_cltools'),
                'fieldname' => 'actions',
                'rawtype' => PARAM_RAW,
            ]);
        }
    }

    /**
     * Change the sort if there was any alias changes.
     *
     * @return array column name => SORT_... constant.
     */
    public function get_sort_columns() {
        $sorts = [];
        if (!$this->issetup) {
            throw new coding_exception('Cannot call get_sort_columns until you have called setup.');
        }

        if (empty($this->sortdata)) {
            return array();
        }
        foreach($this->sortdata as $sortcolumn => $sortorder) {
            if (!empty($this->fieldaliases[$sortcolumn])) {
                $sortcolumn = $this->fieldaliases[$sortcolumn];
            }
            $sorts[$sortcolumn] = $sortorder;
        }

        return $sorts;
    }
}
