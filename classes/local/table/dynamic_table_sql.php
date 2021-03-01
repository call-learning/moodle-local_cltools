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
use local_cltools\local\filter\filterset;
use table_sql;

abstract class dynamic_table_sql extends table_sql {
    /**
     * @var filterset The currently applied filerset
     * This is required for dynamic tables, but can be used by other tables too if desired.
     */
    protected $filterset = null;

    protected $formatters = [];

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
    public function retrieve_row_data($pagesize) {
        global $DB;
        if (!$this->columns) {
            $onerow = $DB->get_record_sql("SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params, IGNORE_MULTIPLE);
            //if columns is not set then define columns as the keys of the rows returned
            //from the db.
            $this->define_columns(array_keys((array) $onerow));
            $this->define_headers(array_keys((array) $onerow));
        }
        $this->setup();
        $this->query_db($pagesize, false);
        foreach ($this->rawdata as $row) {
            $formattedrow = $this->format_row($row);
            $rows[] = (object) $formattedrow;
        }
        $this->close_recordset();
        return $rows;
    }

    public function get_columns() {
        $columnsdef = [];
        $this->setup();
        foreach ($this->columns as $fieldid => $index) {
            $formatter = 'html';
            if (!empty($this->formatters[$fieldid])) {
                $formatter = $this->formatters[$fieldid];
            }
            $columnsdef[] = (object) [
                'title' => $this->headers[$index],
                'field' => $fieldid,
                'visible' => $fieldid == 'id' ? false : true,
                'formatter' => $formatter
            ];
        }
        return $columnsdef;
    }
}
