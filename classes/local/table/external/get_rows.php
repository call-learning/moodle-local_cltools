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

namespace local_cltools\local\table\external;
defined('MOODLE_INTERNAL') || die;

use core_table\local\filter\filter;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_cltools\local\table\dynamic_table_interface;
use local_cltools\local\table\dynamic_table_sql;
use UnexpectedValueException;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Get rows
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_rows extends external_api {

    /**
     * External function to get the table view content.
     *
     * @param string $handler Dynamic table class name.
     * @param string $handlerparams Handler params
     * @param string $uniqueid Unique ID for the container.
     * @param array|null $sortdata The columns and order to sort by
     * @param array|null $filters The filters that will be applied in the request.
     * @param int|null $jointype The join type.
     * @param bool $editable
     * @param array|null $actionsdefs Actions definition
     * @param array|null $hiddencolumns *
     * @param int|null $pagenumber The page number from 1 to n.
     * @param int|null $pagesize The number of records per page.
     *
     * @return array
     */
    public static function execute(
        string $handler,
        string $handlerparams,
        string $uniqueid,
        ?array $sortdata = [],
        ?array $filters = [],
        ?int $jointype = filter::JOINTYPE_NONE,
        bool $editable = false,
        ?array $actionsdefs = [],
        ?array $hiddencolumns = [],
        ?int $pagenumber = null,
        ?int $pagesize = null
    ) {
        global $PAGE;
        [
            'handler' => $handler,
            'handlerparams' => $handlerparams,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'editable' => $editable,
            'actionsdefs' => $actionsdefs,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns
        ] = self::validate_parameters(self::execute_parameters(), [
            'handler' => $handler,
            'handlerparams' => $handlerparams,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'editable' => $editable,
            'actionsdefs' => $actionsdefs,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
        ]);

        /* @var $instance dynamic_table_interface the dynamic table itself */
        $instance = helper::get_table_handler_instance($handler, $handlerparams, $uniqueid, $editable, $actionsdefs);

        helper::setup_filters($instance, $filters, $jointype);

        /* @var dynamic_table_sql $instance instance */
        // TODO : correct this, we should be able to rely on the default value.
        if ($pagesize === 0 || $pagenumber < 0 || empty($pagenumber)) {
            $instance->set_pageable(false);
        } else {
            $instance->set_pageable(true);
            $instance->set_page_number($pagenumber);
        }
        // Convert from an array of sort definition to column => sortorder.
        if (!empty($sortdata)) {
            $instance->set_sortdata($sortdata);
        }
        $context = helper::get_current_context();
        self::validate_context($context);
        if (!$instance::validate_access($context)) {
            throw new \restricted_context_exception();
        }
        $PAGE->set_context($context);

        $rows = $instance->get_rows($pagesize);
        if (!empty($rows) && empty($rows[0]->id)) {
            throw new UnexpectedValueException("The table handler class {$handler} must be return an id column
             that will then be hidden but keep reference to the row unique identifier.");
        }
        $returnval = [
            'data' => array_map(
                function($r) {
                    return json_encode($r);
                },
                $rows
            ),
        ];
        if ($instance->is_pageable()) {
            $returnval['pagescount'] = intval(floor($instance->get_total_rows() / $instance->get_page_size()));
        }
        return $returnval;
    }

    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array_merge(
                helper::get_table_query_basic_parameters(), [
                    'hiddencolumns' => new external_multiple_structure(
                        new external_value(
                            PARAM_ALPHANUMEXT,
                            'Name of column',
                            VALUE_REQUIRED,
                            null
                        ),
                        VALUE_DEFAULT,
                        []
                    ),
                    'pagenumber' => new external_value(
                        PARAM_INT,
                        'The page number',
                        VALUE_DEFAULT,
                        -1
                    ),
                    'pagesize' => new external_value(
                        PARAM_INT,
                        'The number of records per page',
                        VALUE_DEFAULT,
                        0
                    ),
                ]
            )
        );
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 3.9
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'pagescount' => new external_value(PARAM_INT, 'Maximum page count.', VALUE_OPTIONAL),
            'data' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'JSON encoded values in return.')
            ),
        ]);
    }
}

