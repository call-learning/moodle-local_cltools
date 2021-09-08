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

use coding_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use local_cltools\local\field\entity_selector;
use local_cltools\local\filter\basic_filterset;
use moodle_exception;
use ReflectionClass;
use ReflectionException;
use restricted_context_exception;
use UnexpectedValueException;

defined('MOODLE_INTERNAL') || die;

class external extends external_api {

    /**
     * External function to get the table view content.
     *
     * @param string $handler Dynamic table class name.
     * @param string $uniqueid Unique ID for the container.
     * @param array $sortdata The columns and order to sort by
     * @param array|null $filters The filters that will be applied in the request.
     * @param string|null $jointype The join type.
     * @param array|null $hiddencolumns
     * @param bool $resetpreferences Whether it is resetting table preferences or not.
     *
     * @param int|null $pagenumber The page number.
     * @param int|null $pagesize The number of records.
     * @return array
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_rows(
        string $handler,
        string $uniqueid,
        array $sortdata,
        ?array $filters = null,
        ?string $jointype = null,
        ?bool $editable = false,
        ?array $hiddencolumns = null,
        ?bool $resetpreferences = null,
        ?int $pagenumber = null,
        ?int $pagesize = null
    ) {
        global $PAGE, $CFG;

        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'editable' => $editable,
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
            'editable' => $editable,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
            'resetpreferences' => $resetpreferences,
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid);

        self::setup_filters($instance, $filters, $jointype);
        if ($resetpreferences === true) {
            $instance->mark_table_to_reset();
        }

        $PAGE->set_url($instance->baseurl);

        /* @var dynamic_table_sql $instance */
        // TODO : correct this, we should be able to rely on the default value.
        if ($pagesize === 0 || $pagenumber < 0 || empty($pagenumber)) {
            $instance->pageable(false);
        } else {
            $instance->pageable(true);
            $instance->set_page_number($pagenumber);
        }
        // Convert from an array of sort definition to column => sortorder.
        if (!empty($sortdata)) {
            $sortdef = [];
            foreach ($sortdata as $def) {
                $def = (object) $def;
                $sortdef[$def->sortby] = ($def->sortorder === 'ASC') ? SORT_ASC : SORT_DESC;
            }
            $instance->set_sort_data($sortdef);
        }

        $instance->validate_access();

        $rows = $instance->retrieve_raw_data($pagesize);
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
            )
        ];
        if ($instance->use_pages) {
            $returnval['pagescount'] = floor($instance->totalrows / $instance->pagesize);
        }
        return $returnval;
    }

    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function get_rows_parameters(): external_function_parameters {
        return new external_function_parameters(
            array_merge(
                static::get_table_query_basic_parameters(), [
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
                    'pagenumber' => new external_value(
                        PARAM_INT,
                        'The page number',
                        VALUE_OPTIONAL,
                        -1
                    ),
                    'pagesize' => new external_value(
                        PARAM_INT,
                        'The number of records per page',
                        VALUE_OPTIONAL,
                        0
                    )
                ]
            )
        );
    }

    /**
     * Basic parameters for any query related to the table
     *
     * Note that we include filters as they can somewhat have an influcence on columns
     * selected too.
     *
     * @return external_function_parameters
     */
    public static function get_table_query_basic_parameters(): array {
        return [
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
                    'name' => new external_value(PARAM_ALPHANUM, 'Name of the filter', VALUE_REQUIRED),
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
            'editable' => new external_value(PARAM_BOOL, 'Is table editable ?', VALUE_OPTIONAL, false),
        ];
    }

    /**
     * Get table handler instance
     *
     * @param $handler
     * @param $uniqueid
     * @return mixed
     * @throws ReflectionException
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_table_handler_instance($handler, $uniqueid, $editable = false) {
        global $CFG;

        if (!class_exists($handler)) {
            throw new UnexpectedValueException("Table handler class {$handler} not found. " .
                "Please make sure that your handler is defined.");
        }

        if (!is_subclass_of($handler, dynamic_table_sql::class)) {
            throw new UnexpectedValueException("Table handler class {$handler} does not support dynamic updating.");
        }
        $classfilepath = (new ReflectionClass($handler))->getFileName();
        if (strpos($classfilepath, $CFG->dirroot) !== 0) {
            throw new UnexpectedValueException("Table handler class {$handler} must be defined in
                         {$CFG->dirroot}, instead of {$classfilepath}.");
        }
        $instance = new $handler($uniqueid, null, $editable);
        return $instance;
    }

    public static function setup_filters(&$instance, $filters, $jointype) {
        $instanceclass = get_class($instance);
        $filtersetclass = "{$instanceclass}_filterset";
        if (!class_exists($filtersetclass)) {
            $filtertypedef = [];
            foreach ($filters as $rawfilter) {
                $ftdef = (object) [
                    'filterclass' => 'local_cltools\\local\filter\\' . $rawfilter['type'],
                    'required' => !empty($rawfilter['required']),
                ];
                $filtertypedef[$rawfilter['name']] = $ftdef;
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
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     * @since Moodle 3.9
     */
    public static function get_rows_returns(): external_single_structure {
        return new external_single_structure([
            'pagescount' => new external_value(PARAM_INT, 'Maximum page count.', VALUE_OPTIONAL),
            'data' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'JSON encoded values in return.')
            )
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
        string $uniqueid,
        ?array $filters = null,
        ?string $jointype = null,
        ?bool $editable = null
    ) {
        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'filters' => $filters,
            'jointype' => $jointype,
            'editable' => $editable,
        ] = self::validate_parameters(self::get_columns_parameters(), [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'filters' => $filters,
            'jointype' => $jointype,
            'editable' => $editable,
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid, $editable);
        $instance->validate_access();
        self::setup_filters($instance, $filters, $jointype);
        /* @var $instance dynamic_table_sql */
        $columndefs = array_values($instance->get_fields_definition());

        return $columndefs;
    }

    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function get_columns_parameters(): external_function_parameters {
        return new external_function_parameters(
            static::get_table_query_basic_parameters()
        );
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
                    'filter' => new external_value(PARAM_RAW, 'Filter: image, html, datetime ....', VALUE_OPTIONAL),
                    'filterParams' => new external_value(PARAM_RAW, 'Filter parameter as JSON, ....', VALUE_OPTIONAL),
                    'formatter' => new external_value(PARAM_RAW, 'Formatter: image, html, datetime ....', VALUE_OPTIONAL),
                    'formatterParams' => new external_value(PARAM_RAW, 'Formatter parameter as JSON, ....', VALUE_OPTIONAL),
                    'editor' => new external_value(PARAM_RAW, 'Editor: image, html, datetime ....', VALUE_OPTIONAL),
                    'editorParams' => new external_value(PARAM_RAW, 'Editor: parameter as JSON, ....', VALUE_OPTIONAL),
                    'validator' => new external_value(PARAM_RAW, 'Validator: image, html, datetime ....', VALUE_OPTIONAL),
                    'validatorParams' => new external_value(PARAM_RAW, 'Validator: parameter as JSON, ....', VALUE_OPTIONAL),
                    'additionalParams' => new external_value(PARAM_RAW, 'Additional params in the form of a JSON object,
                      will be merged with column definition', VALUE_OPTIONAL),
                ])
            );
    }

    /**
     * Set a field value
     *
     * @param $handler
     * @param $uniqueid
     * @param $id
     * @param $field
     * @param $value
     * @param $oldvalue
     * @return array
     * @throws ReflectionException
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function set_value($handler, $uniqueid, $id, $field, $value, $oldvalue) {
        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'id' => $id,
            'field' => $field,
            'value' => $value,
            'oldvalue' => $oldvalue,
        ] = self::validate_parameters(self::set_value_parameters(), [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'id' => $id,
            'field' => $field,
            'value' => $value,
            'oldvalue' => $oldvalue,
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid, true);
        $instance->validate_access(true);
        $success = false;
        $warnings = array();
        try {
            $success = $instance->set_value($id, $field, $value, $oldvalue);
        } catch (moodle_exception $e) {
            $warnings[] = (object) [
                'item' => $field,
                'itemid' => $id,
                'warningcode' => 'setvalueerror',
                'message' => "For table $handler: {$e->getMessage()}"
            ];
        }
        return [
            'success' => $success,
            'warnings' => $warnings
        ];
    }

    /**
     * Set value parameters
     */
    public static function set_value_parameters() {
        return new external_function_parameters (
            [
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
                'id' => new external_value(
                    PARAM_INT,
                    'Data id',
                    VALUE_REQUIRED
                ),
                'field' =>
                    new external_value(
                        PARAM_ALPHANUMEXT,
                        'Name of the field',
                        VALUE_REQUIRED
                    ),
                'value' =>
                    new external_value(
                        PARAM_RAW,
                        'New value',
                        VALUE_REQUIRED
                    ),
                'oldvalue' =>
                    new external_value(
                        PARAM_RAW,
                        'Old value',
                        VALUE_REQUIRED
                    ),
            ]
        );

    }

    /**
     * Set value returns
     *
     * @return external_single_structure
     */
    public static function set_value_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'True if the value was updated, false otherwise.'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Set a field value
     *
     * @param $handler
     * @param $uniqueid
     * @param $id
     * @param $field
     * @param $value
     * @param $oldvalue
     * @return array
     * @throws ReflectionException
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function is_value_valid($handler, $uniqueid, $id, $field, $value) {
        [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'id' => $id,
            'field' => $field,
            'value' => $value,
        ] = self::validate_parameters(self::is_value_valid_parameters(), [
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'id' => $id,
            'field' => $field,
            'value' => $value
        ]);

        $instance = self::get_table_handler_instance($handler, $uniqueid, true);
        $instance->validate_access();
        $success = false;
        $warnings = array();
        try {
            $success = $instance->is_valid_value($id, $field, $value);
        } catch (moodle_exception $e) {
            $warnings[] = (object) [
                'item' => $field,
                'itemid' => $id,
                'warningcode' => 'setvalueerror',
                'message' => "For table $handler: {$e->getMessage()}"
            ];
        }
        return [
            'success' => $success,
            'warnings' => $warnings
        ];
    }

    /**
     * Set value parameters
     */
    public static function is_value_valid_parameters() {
        return new external_function_parameters (
            [
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
                'id' => new external_value(
                    PARAM_INT,
                    'Data id',
                    VALUE_REQUIRED
                ),
                'field' =>
                    new external_value(
                        PARAM_ALPHANUMEXT,
                        'Name of the field',
                        VALUE_REQUIRED
                    ),
                'value' =>
                    new external_value(
                        PARAM_RAW,
                        'New value',
                        VALUE_REQUIRED
                    )
            ]
        );

    }

    /**
     * Set value returns
     *
     * @return external_single_structure
     */
    public static function is_value_valid_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'True if the value was updated, false otherwise.'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Entity lookup value
     *
     * @param $handler
     * @param $uniqueid
     * @param $id
     * @param $field
     * @param $value
     * @param $oldvalue
     * @return array
     * @throws ReflectionException
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function entity_lookup($entityclass, $displayfield) {
        [
            'entityclass' => $entityclass,
            'displayfield' => $displayfield,
        ] = self::validate_parameters(self::entity_lookup_parameters(), [
            'entityclass' => $entityclass,
            'displayfield' => $displayfield,
        ]);
        $values = [];
        $warnings = [];
        try {
            $values = entity_selector::entity_lookup($entityclass, $displayfield);
        } catch (moodle_exception $e) {
            $warnings[] = (object) [
                'entityclass' => $entityclass,
                'displayfield' => $displayfield,
                'warningcode' => 'lookuperror',
                'message' => "For entity $entityclass: {$e->getMessage()}"
            ];
        }
        return [
            'values' => json_encode($values),
            'warnings' => $warnings
        ];
    }

    /**
     * Entity lookup value parameters
     */
    public static function entity_lookup_parameters() {
        return new external_function_parameters (
            [
                'entityclass' => new external_value(
                // Note: We do not have a PARAM_CLASSNAME which would have been ideal.
                // For now we will have to check manually.
                    PARAM_RAW,
                    'Handler',
                    VALUE_REQUIRED
                ),
                'displayfield' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Name of the field used to display values',
                    VALUE_OPTIONAL
                )
            ]
        );

    }

    /**
     * Entity lookup value returns
     *
     * @return external_single_structure
     */
    public static function entity_lookup_returns() {
        return new external_single_structure(
            array(
                'values' => new external_value(PARAM_RAW, 'Associative array as json.'),
                'warnings' => new external_warnings()
            )
        );
    }

}
