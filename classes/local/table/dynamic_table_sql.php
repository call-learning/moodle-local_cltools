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
use local_cltools\local\field\text;
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

    protected $fields = [];

    /**
     * Get the context for the table.
     *
     * This is used by the API to validate the fact that the user can execute the query.
     *
     * @return context
     */
    public abstract function get_context();

    /**
     * Set the filterset in the table class.
     *
     * The use of filtersets is a requirement for dynamic tables, but can be used by other tables too if desired.
     *
     * @param filterset $filterset The filterset object to get filters and table parameters from
     */
    public function set_filterset(filterset $filterset): void {
        $this->filterset = $filterset;
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
        $currpage = $this->currpage;
        parent::setup();
        $this->currpage = $currpage ? $currpage : $this->currpage;
    }

    public function get_columns() {
        $columnsdef = [];
        $this->setup();
        foreach ($this->columns as $fieldid => $index) {
            $column = (object) [
                'title' => $this->headers[$index],
                'field' => $fieldid,
                'visible' => $fieldid == 'id' ? false : true,
            ];
            if (!empty($this->fields[$fieldid])) {
                $field = $this->fields[$fieldid];
                $column->formatter = $field->get_type();
                if ($field->get_formatter_parameters()) {
                    $column->formatterparams = json_encode($field->get_formatter_parameters());
                }
                $column->filter = $field->get_type();
                if ($field->get_filter_parameters()) {
                    $column->filterparams = json_encode($field->get_filter_parameters());
                }
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

    public function query_db($pagesize, $useinitialsbar = true) {
        $directiontosql = [
            '=' => '=',
            '==' => '=',
            '===' => '=',
            '>' => '>',
            '=>' => '>=',
            '<' => '<',
            '<=' => '<=',
        ];
        // Make sure we first check the filters.
        $additionalwhere = '';
        if (!empty($filters = $this->filterset->get_filters())) {
            $paramcount = 0;
            foreach ($filters as $filter) {
                $filtervalues = $filter->get_filter_values();
                $join ='AND';
                switch($filter->get_join_type()) {
                    case filterset::JOINTYPE_ALL:
                        $join = 'AND';
                        break;
                    case filterset::JOINTYPE_ANY:
                        $join = 'OR';
                        break;
                }

                foreach($filtervalues as $fval) {
                    $paramname = "param_{$paramcount}";
                    if (!empty($additionalwhere)) {
                        $additionalwhere .= $join;
                    }
                    $additionalwhere .= " {$filter->get_name()}  {$directiontosql[$fval->direction]}  :$paramname ";
                    $this->sql->params[$paramname] = $fval->value;
                    $paramcount++;
                }
            }
            $additionalwhere = "( $additionalwhere )";
            if (!empty($this->sql->where)) {
                $additionalwhere = " AND $additionalwhere";
            }
        }
        $this->sql->where .= $additionalwhere;
        parent::query_db($pagesize, $useinitialsbar);
    }
}
