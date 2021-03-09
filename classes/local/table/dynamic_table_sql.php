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
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\table;
global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use context;
use local_cltools\local\field\hidden;
use local_cltools\local\filter\filterset;
use table_sql;

abstract class dynamic_table_sql extends table_sql {
    /** @var array list of user fullname shown in report. This is a way to store temporarilly the usernames and
     * avoid hitting the DB too much
     */
    private $userfullnames = array();

    /**
     * @var filterset The currently applied filerset
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
     * @var array $filteraliases an associative array that will set the right sql alias for this table if needed
     */
    protected $filteraliases = null;

    /**
     * Sets up the page_table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @throws \coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid,
        $actionsdefs = null
    ) {
        parent::__construct($uniqueid);
        $this->actionsdefs = $actionsdefs;
        list($cols, $headers) = $this->get_table_columns_definitions();
        $this->define_columns($cols);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->set_attribute('class', 'generaltable generalbox table-sm');
        $this->set_entity_sql();
    }

    /**
     * Set the filterset in the table class.
     *
     * The use of filtersets is a requirement for dynamic tables, but can be used by other tables too if desired.
     * This also sets the filter aliases if not set for each filters, depending on what is set in the
     * local $filteralias array.
     * @param filterset $filterset The filterset object to get filters and table parameters from
     */
    public function set_extended_filterset(filterset $filterset): void {
        foreach($this->filteraliases as $filtername => $sqlalias) {
            if ($filterset->has_filter($filtername)) {
                $filter = $filterset->get_filter($filtername);
                $filter->set_alias($sqlalias);
            }
        }
        $this->filterset = $filterset;
    }

    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     * @throws \dml_exception
     */
    public function validate_access() {
        external::validate_context(\context_system::instance());
    }

    /**
     * Set the page number.
     *
     * @param int $pagenumber The page number.
     */
    public function set_page_number(int $pagenumber): void {
        $this->currpage = $pagenumber - 1;
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
     * Retrieve data from the database and return a row set
     *
     * @return array
     */
    public function retrieve_raw_data($pagesize) {
        $this->setup();
        $this->query_db($pagesize, false);
        $rows = [];
        foreach ($this->rawdata as $row) {
            $formattedrow = $this->format_row($row);
            $rows[] = (object) $formattedrow;
        }
        $this->close_recordset();
        return $rows;
    }

    /**
     * Must be called after table is defined. Use methods above first. Cannot
     * use functions below till after calling this method.
     *
     * @return type?
     */
    function setup() {
        if ($this->use_pages) {
            $currpage = $this->currpage;
        }
        parent::setup();
        if ($this->use_pages) {
            $this->currpage = $currpage ? $currpage : $this->currpage;
        }
    }


    /**
     * Sets the pagesize variable to the given integer, the totalrows variable
     * to the given integer, and the use_pages variable to true.
     * @param int $perpage
     * @param int $total
     * @return void
     */
    function pagesize($perpage, $total) {
        if($this->use_pages) {
            $this->pagesize = $perpage;
            $this->totalrows = $total;
            $this->use_pages = true;
        }
    }
    /**
     * Table columns
     *
     * @return array[]
     * @throws \coding_exception
     */
    protected function get_table_columns_definitions() {
        // Create the related persistent filter form.
        $cols = [];
        $headers = [];

        $this->setup_fields();
        foreach ($this->fields as $name => $field) {
            $cols[] = $name;
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

    public function get_filter_set() {
        return $this->filterset;
    }

    /**
     * @throws \coding_exception
     */
    protected function setup_other_fields() {
        if ($this->actionsdefs) {
            $this->fields['actions'] = base::get_instance_from_def('html', [
                'fullname' => get_string('actions', 'local_cltools'),
                'fieldname' => 'actions',
                'rawtype' => PARAM_RAW,
            ]);
        }
    }

    /**
     * @return array
     */
    public function get_fields_definition() {
        $columnsdef = [];
        $this->setup();
        foreach ($this->columns as $fieldid => $index) {
            $field = $this->fields[$fieldid];
            $column = (object) [
                'title' => $this->headers[$index],
                'field' => $fieldid,
                'visible' => $field->is_visible(),
            ];

            if ($field->get_formatter_type()) {
                $column->formatter = $field->get_formatter_type();
                if ($field->get_formatter_parameters()) {
                    $column->formatterparams = json_encode($field->get_formatter_parameters());
                }
            }
            if ($field->get_filter_type()) {
                $column->filter = $field->get_filter_type();
                if ($field->get_filter_parameters()) {
                    $column->filterparams = json_encode($field->get_filter_parameters());
                }
            }
            $colmethodname = 'col_'.$fieldid;
            // Disable sorting and formatting for all formatted rows.
            if (method_exists($this, $colmethodname)) {
                unset($column->filter);
                unset($column->filterparams);
                $column->formatter = 'html';
                unset($column->formatterparams);
            }

            $columnsdef[] = $column;
        }

        return $columnsdef;
    }

    /**
     * Set the user preference for sorting order
     *
     */
    public function set_sort_data($sortdef) {
        global $SESSION;

        // Load any existing user preferences.
        $prefs = null;
        if ($this->is_persistent()) {
            $prefs = json_decode(get_user_preferences('flextable_' . $this->uniqueid), true);
        } else if (isset($SESSION->flextable[$this->uniqueid])) {
            $prefs = $SESSION->flextable[$this->uniqueid];
        }

        $prefs['sortby'] = $sortdef;

        if ($this->is_persistent()) {
            set_user_preference('flextable_' . $this->uniqueid, json_encode($prefs));
        } else {
            $SESSION->flextable[$this->uniqueid] = $prefs;
        }
    }

    /**
     * Gets the user full name helper
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullname it will fetch it from db.
     *
     * @param int $userid
     * @return false|\lang_string|mixed|string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_user_fullname($userid) {
        global $DB;

        if (empty($userid)) {
            return false;
        }

        if (!empty($this->userfullnames[$userid])) {
            return $this->userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if (isset($this->userfullnames[$userid]) && $this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_record_sql($sql, $uparams)) {
            $this->userfullnames[$userid] = false;
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

    /**
     * Get time helper
     *
     * @param $time
     * @return string
     * @throws \coding_exception
     */
    protected function get_time($time) {
        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($time, $dateformat);
    }

    /**
     * Main method to create the underlying query (SQL)
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        list($additionalwhere, $additionalparams) = $this->filterset->get_sql_for_filter();
        if ($additionalwhere) {
            if (!empty($this->sql->where)) {
                $this->sql->where .= " AND ($additionalwhere)";
            } else {
                $this->sql->where = "$additionalwhere";
            }
            $this->sql->params += $additionalparams;
        }
        $this->sql->fields =  "DISTINCT ".$this->sql->fields;
        if ($this->countsql === NULL) {
            $this->countsql = "SELECT COUNT(1) FROM (SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}) 
            squery";
            $this->countparams = $this->sql->params;
        }
        parent::query_db($pagesize, $useinitialsbar);
    }

}
