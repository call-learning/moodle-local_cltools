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
 * External function / API for dynamic table
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\table;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use local_cltools\local\filter\basic_filterset;

class external extends \external_api {
    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function get_rows_parameters(): external_function_parameters {
        return new external_function_parameters ([
            'handler' => new external_value(
            // Note: We do not have a PARAM_CLASSNAME which would have been ideal.
            // For now we will have to check manually.
                PARAM_RAW,
                'Handler',
                VALUE_REQUIRED
            ),
            'uniqueid' => new external_value(
                PARAM_ALPHANUMEXT,
                'Unique ID for the container',
                VALUE_REQUIRED
            ),
            'sortdata' => new external_multiple_structure(
                new external_single_structure([
                    'sortby' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'The name of a sortable column',
                        VALUE_REQUIRED
                    ),
                    'sortorder' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'The direction that this column should be sorted by',
                        VALUE_REQUIRED
                    ),
                ]),
                'The combined sort order of the table. Multiple fields can be specified.',
                VALUE_OPTIONAL,
                []
            ),
            'filters' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Type of filter', VALUE_REQUIRED),
                    'name' => new external_value(PARAM_ALPHANUM, 'Name of the field', VALUE_REQUIRED),
                    'jointype' => new external_value(PARAM_INT, 'Type of join for filter values', VALUE_REQUIRED),
                    'required' => new external_value(PARAM_BOOL, 'Is this a required filter', VALUE_OPTIONAL),
                    'values' => new external_multiple_structure(
                        new external_value(PARAM_RAW, 'Filter value'),
                        'The value to filter on',
                        VALUE_REQUIRED
                    )
                ]),
                'The filters that will be applied in the request',
                VALUE_OPTIONAL
            ),
            'jointype' => new external_value(PARAM_INT, 'Type of join to join all filters together', VALUE_REQUIRED),
            'firstinitial' => new external_value(
                PARAM_RAW,
                'The first initial to sort filter on',
                VALUE_REQUIRED,
                null
            ),
            'lastinitial' => new external_value(
                PARAM_RAW,
                'The last initial to sort filter on',
                VALUE_REQUIRED,
                null
            ),
            'pagenumber' => new external_value(
                PARAM_INT,
                'The page number',
                VALUE_REQUIRED,
                null
            ),
            'pagesize' => new external_value(
                PARAM_INT,
                'The number of records per page',
                VALUE_REQUIRED,
                null
            ),
            'hiddencolumns' => new external_multiple_structure(
                new external_value(
                    PARAM_ALPHANUMEXT,
                    'Name of column',
                    VALUE_REQUIRED,
                    null
                )
            ),
            'resetpreferences' => new external_value(
                PARAM_BOOL,
                'Whether the table preferences should be reset',
                VALUE_REQUIRED,
                null
            ),
        ]);
    }

    /**
     * External function to get the table view content.
     *
     * @param string $component The component.
     * @param string $handler Dynamic table class name.
     * @param string $uniqueid Unique ID for the container.
     * @param array $sortdata The columns and order to sort by
     * @param array $filters The filters that will be applied in the request.
     * @param string $jointype The join type.
     * @param string $firstinitial The first name initial to filter on
     * @param string $lastinitial The last name initial to filter on
     * @param int $pagenumber The page number.
     * @param int $pagesize The number of records.
     * @param string $jointype The join type.
     * @param bool $resetpreferences Whether it is resetting table preferences or not.
     *
     * @return array
     */
    public static function get_rows(
        string $handler,
        string $uniqueid,
        array $sortdata,
        ?array $filters = null,
        ?string $jointype = null,
        ?string $firstinitial = null,
        ?string $lastinitial = null,
        ?int $pagenumber = null,
        ?int $pagesize = null,
        ?array $hiddencolumns = null,
        ?bool $resetpreferences = null
    ) {
        global $PAGE, $CFG;

        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'firstinitial' => $firstinitial,
            'lastinitial' => $lastinitial,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
            'resetpreferences' => $resetpreferences,
        ] = self::validate_parameters(self::get_rows_parameters(), [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'firstinitial' => $firstinitial,
            'lastinitial' => $lastinitial,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
            'resetpreferences' => $resetpreferences,
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid);


        $instanceclass= get_class($instance);
        $filtersetclass = "{$instanceclass}_filterset";
        if (!class_exists($filtersetclass)) {
            $filtertypedef =  [];
            foreach ($filters as $rawfilter) {
                $filtertypedef[$rawfilter['name']] = (object) [
                    'filterclass' => 'local_cltools\\local\filter\\'. $rawfilter['type'],
                    'required' =>  !empty($rawfilter['required'])
                ];
            }
            $filterset = new basic_filterset($filtertypedef);
        } else {
            $filterset = new $filtersetclass();
        }
        $filterset->set_join_type($jointype);
        foreach ($filters as $rawfilter) {
            $filterset->add_filter_from_params(
                $rawfilter['name'], // Field name.
                $rawfilter['jointype'],
                $rawfilter['values']
            );
        }

        $instance->set_extended_filterset($filterset);
        if ($pagenumber !== null) {
            $instance->set_page_number($pagenumber);
        }

        if ($pagesize === null) {
            $pagesize = 20;
        }

        if ($resetpreferences === true) {
            $instance->mark_table_to_reset();
        }

        $PAGE->set_url($instance->baseurl);

        /* @var dynamic_table_sql $instance */
        $instance->pageable(true);
        $instance->set_page_number($pagenumber);
        // Convert from an array of sort definition to column => sortorder
        if (!empty($sortdata)) {
            $sortdef = [];
            foreach ($sortdata as $def) {
                $def = (object) $def;
                $sortdef[$def->sortby] = ($def->sortorder === 'ASC') ? SORT_ASC : SORT_DESC;
            }
            $instance->set_sort_data($sortdef);
        }

        $instance->build_table();

        $rows = $instance->retrieve_raw_data($pagesize);
        if (!empty($rows) && empty($rows[0]->id)) {
            throw new \UnexpectedValueException("The table handler class {$handler} must be return an id column 
            that will then be hidden but keep reference to the row unique identifier.");
        }

        return [
            'pagescount' => floor($instance->totalrows / $instance->pagesize),
            "data" => array_map(
                function($r) {
                    return json_encode($r);
                },
                $rows
            )
        ];
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 3.9
     */
    public static function get_rows_returns(): external_single_structure {
        return new external_single_structure([
            'pagescount' => new external_value(PARAM_INT, 'Maximum page count.'),
            'data' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'JSON encoded values in return.')
            )
        ]);
    }

    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function get_columns_parameters(): external_function_parameters {
        return new external_function_parameters ([
            'handler' => new external_value(
            // Note: We do not have a PARAM_CLASSNAME which would have been ideal.
            // For now we will have to check manually.
                PARAM_RAW,
                'Handler',
                VALUE_REQUIRED
            ),
            'uniqueid' => new external_value(
                PARAM_ALPHANUMEXT,
                'Unique ID for the container',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * External function to get the table view content.
     *
     * @param string $component The component.
     * @param string $handler Dynamic table class name.
     * @param string $uniqueid Unique ID for the container.
     * @param array $sortdata The columns and order to sort by
     * @param array $filters The filters that will be applied in the request.
     * @param string $jointype The join type.
     * @param string $firstinitial The first name initial to filter on
     * @param string $lastinitial The last name initial to filter on
     * @param int $pagenumber The page number.
     * @param int $pagesize The number of records.
     * @param string $jointype The join type.
     * @param bool $resetpreferences Whether it is resetting table preferences or not.
     *
     * @return array
     */
    public static function get_columns(
        string $handler,
        string $uniqueid
    ) {
        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
        ] = self::validate_parameters(self::get_columns_parameters(), [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid);
        $columndefs = $instance->get_fields_definition();

        return $columndefs;
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_multiple_structure
     * @since Moodle 3.9
     */
    public static function get_columns_returns(): external_multiple_structure {
        return
            new external_multiple_structure(
                new external_single_structure([
                    'title' => new external_value(PARAM_TEXT, 'Column title.'),
                    'field' => new external_value(PARAM_TEXT, 'Field name in reference to this title.'),
                    'visible' => new external_value(PARAM_BOOL, 'Is visible ?.'),
                    'filter' => new external_value(PARAM_ALPHANUMEXT, 'Filter: image, html, datetime ....', VALUE_OPTIONAL),
                    'filterparams' => new external_value(PARAM_RAW, 'Filter parameter as JSON, ....', VALUE_OPTIONAL),
                    'formatter' => new external_value(PARAM_ALPHANUMEXT, 'Formatter: image, html, datetime ....', VALUE_OPTIONAL),
                    'formatterparams' => new external_value(PARAM_RAW, 'Formatter parameter as JSON, ....', VALUE_OPTIONAL),
                ])
            );
    }

    public static function get_table_handler_instance($handler, $uniqueid) {
        global $CFG;

        if (!class_exists($handler)) {
            throw new \UnexpectedValueException("Table handler class {$handler} not found. " .
                "Please make sure that your handler is defined.");
        }

        if (!is_subclass_of($handler, dynamic_table_sql::class)) {
            throw new \UnexpectedValueException("Table handler class {$handler} does not support dynamic updating.");
        }
        $classfilepath = (new \ReflectionClass($handler))->getFileName();
        if (strpos($classfilepath, $CFG->dirroot) !== 0) {
            throw new \UnexpectedValueException("Table handler class {$handler} must be defined in
                         {$CFG->dirroot}, instead of {$classfilepath}.");
        }
        $instance = new $handler($uniqueid);
        self::validate_context($instance->get_dynamic_table_context());
        return $instance;
    }

}