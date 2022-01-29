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

use coding_exception;
use context;
use core_table\local\filter\filterset;
use html_writer;
use local_cltools\local\field\blank_field;
use local_cltools\local\field\hidden;
use local_cltools\local\field\persistent_field;
use local_cltools\local\filter\enhanced_filterset;
use local_cltools\local\table\external\helper;
use moodle_exception;
use moodle_url;
use pix_icon;
use popup_action;

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
     * @var array<persistent_field> field defintions
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
        $this->uniqueid = $uniqueid ? $uniqueid : html_writer::random_id('dynamictable');
        $this->actionsdefs = $actionsdefs;
        $this->iseditable = (bool) $editable;
        list($cols, $headers) = $this->get_table_columns_definitions();
        $this->define_columns($cols);
        $this->define_headers($headers);
        $this->set_sortable(true);
        $this->set_pageable(true);
        $this->setup();
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
        if ($this->actionsdefs) {
            $this->fields[] = new blank_field([
                    'fieldname' => 'actions',
                    'fullname' => get_string('actions', 'local_cltools')
            ]);
        }
        $hasidfield = false;
        foreach ($this->fields as $f) {
            if ($f->get_name() == 'id') {
                $hasidfield = true;
            }
        }
        if (!$hasidfield) {
            $this->fields[] = new hidden('id');
        }
        foreach ($this->fields as $field) {
            $cols[] = $field->get_name();
            $headers[] = $field->get_display_name();
        }
        return [$cols, $headers];
    }

    /**
     * Setup the fields for this table
     */
    abstract protected function setup_fields();

    /**
     * Get the currently defined filterset.
     *
     * @return filterset|null
     */
    public function get_filterset(): ?filterset {
        return $this->filterset;
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
            $aliases = $filterset->get_aliases();
            // TODO: refactor this code: we should be able to merge filterdefs.
            foreach ($filterset->get_required_filters() as $filtername => $filterclass) {
                $filter = [
                        'required' => true,
                        'filterclass' => $filterclass
                ];
                if (!empty($aliases[$filtername])) {
                    $filter['alias'] = $aliases[$filtername];
                }
                $this->filterset->add_filter_definition($filtername, (object) $filter);
            }
            foreach ($filterset->get_optional_filters() as $filtername => $filterdef) {
                $filter = [
                        'optional' => true,
                        'filterclass' => $filterclass
                ];
                if (!empty($aliases[$filtername])) {
                    $filter['alias'] = $aliases[$filtername];
                }
                $this->filterset->add_filter_definition($filtername, (object) $filter);
            }
            foreach ($filterset->get_optional_filters() as $filtername => $filterdef) {
                $this->filterset->add_filter_definition($filtername, (object) [
                        'optional' => true,
                        'filterclass' => $filterclass
                ]);
            }
            foreach ($filterset->get_filters() as $filter) {
                $this->filterset->add_filter($filter);
            }
        } else {
            $this->filterset = $filterset;
        }
    }

    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     *
     * @param context $context
     * @param bool $writeaccess
     * @return mixed
     * @throws \restricted_context_exception
     */
    public function validate_access(context $context, $writeaccess = false) {
        helper::validate_context($context);
    }

    /**
     * Retrieve data from the database and return a row set
     * This can be a superset or a modified of what actual is in the table.
     *
     * @return array
     */
    public function get_rows($pagesize) {
        $rows = [];
        if ($this->setup()) {
            $this->query_db($pagesize);
            foreach ($this->rawdata as $row) {
                $formattedrow = $this->format_row($row);
                $rows[] = (object) $formattedrow;
            }
            $this->close_recordset();
        }
        return $rows;
    }

    /**
     * Get sql fields
     *
     * Overridable sql query
     *
     * @param string $tablealias
     */
    protected function internal_get_sql_fields($tablealias = 'e') {
        $fieldlist = [];
        foreach ($this->fields as $field) {
            $fieldname = $field->get_name();
            $this->fieldaliases[$fieldname] = "entity.{$fieldname}";
            $fieldlist[] = $this->internal_get_sql_for_field($field, $tablealias);
            $additionalfields = $field->get_additional_fields('entity');
            foreach ($additionalfields as $f) {
                $fieldlist[] = $f;
            }
            $this->fieldaliases[$field->get_name()] = "{$tablealias}.{$field->get_name()}";
        }
        return "DISTINCT " . join(',', $fieldlist) . " ";
    }

    /**
     * Get SQL for field
     *
     * @param persistent_field $field
     * @param string $tablealias
     * @return string
     */
    protected function internal_get_sql_for_field($field, $tablealias) {
        $fieldname = $field->get_name();
        if ($field->is_persistent()) {
            $fieldsql = "{$tablealias}.{$fieldname} AS {$fieldname}";
        } else {
            $emptyvalue = "''";
            switch ($field->get_raw_param_type()) {
                case PARAM_INT:
                case PARAM_BOOL:
                    $emptyvalue = "0";
                    break;
                case PARAM_FLOAT:
                    $emptyvalue = "0.0";
                    break;
            }
            $fieldsql = "$emptyvalue AS {$fieldname}";
        }
        return $fieldsql;
    }

    /**
     * Get sql sort
     * Overridable sql query
     */
    protected function internal_get_sql_sort() {
        $sort = $this->construct_order_by($this->get_sort_columns());
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        return $sort;
    }

    /**
     * Get where
     *
     * @param bool $disablefilters
     * @return array
     */
    protected function internal_get_sql_where($disablefilters = false) {
        $sqlwhere = "1=1";
        $sqlparams = [];
        if (!empty($this->filterset) && !$disablefilters) {
            list($additionalsqlwhere, $additionalsqlparams) = $this->filterset->get_sql_for_filter(null, null, $this->fieldaliases);
            if (trim($additionalsqlwhere)) {
                $sqlwhere = "$sqlwhere AND $additionalsqlwhere";
                $sqlparams = $sqlparams ?? [];
                $sqlparams += $additionalsqlparams;
            }
        }
        return [$sqlwhere, $sqlparams];
    }

    /**
     * Overridable sql query
     *
     * @param string $tablealias
     */
    abstract protected function internal_get_sql_from($tablealias = 'e');

    /**
     * Get SQL query parts for this table
     *
     * This is mainly a helper used to get the same or similar info as we would get through
     * the get_rows but in a raw format
     *
     * @param bool $disablefilters disable filters
     * @return array with [$fields, $from, $where, $sort]
     */
    public function get_sql_query($disablefilters = false) {
        $fields = $this->internal_get_sql_fields();
        $sqlfrom = $this->internal_get_sql_from();
        [$sqlwhere, $sqlparams] = $this->internal_get_sql_where($disablefilters);
        $sqlsort = $this->internal_get_sql_sort();
        return [$fields, $sqlfrom, $sqlwhere, $sqlparams, $sqlsort];
    }

    /**
     * Main method to create the underlying query (SQL)
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param bool $disablefilters disable filters
     */
    public function query_db($pagesize, $disablefilters = false) {
        global $DB;
        [$fields, $sqlfrom, $sqlwhere, $sqlparams, $sqlsort] =
                $this->get_sql_query($disablefilters);

        $countsql = "SELECT COUNT(1) FROM (SELECT {$fields}
            FROM {$sqlfrom}
            WHERE {$sqlwhere}) squery";
        $countparams = $sqlparams;
        $grandtotal = $DB->count_records_sql($countsql, $countparams);

        $this->set_pagesize($pagesize ?? $grandtotal, $grandtotal);
        $sql = "SELECT
                {$fields}
                FROM {$sqlfrom}
                WHERE {$sqlwhere}
                {$sqlsort}";
        $this->rawdata = $DB->get_recordset_sql($sql, $sqlparams);
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
        foreach ($this->sortdata as $sortcolumn => $sortorder) {
            if (!empty($this->fieldaliases[$sortcolumn])) {
                $sortcolumn = $this->fieldaliases[$sortcolumn];
            }
            $sorts[$sortcolumn] = $sortorder;
        }

        return $sorts;
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
                    $sortorder = (int) $sortitem['sortorder'];
                } else {
                    $sortorder = ($sortitem['sortorder'] === 'ASC') ? SORT_ASC : SORT_DESC;
                }
                $this->sortdata[$sortitem['sortby']] = $sortorder;
            }
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
    }

    /**
     * Format the actions cell.
     *
     * @param $row
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function col_actions($row) {
        // That will render a template from a json instead.
        global $OUTPUT;
        $actions = [];
        foreach ($this->actionsdefs as $k => $a) {
            $url = new moodle_url($a->url, ['id' => $row->id]);
            $popupaction = empty($a->popup) ? null :
                    new popup_action('click', $url);
            $actions[] = $OUTPUT->action_icon(
                    $url,
                    new pix_icon($a->icon,
                            get_string($k, $a->component ?? 'local_cltools')),
                    $popupaction
            );
        }

        return implode('&nbsp;', $actions);
    }
}
