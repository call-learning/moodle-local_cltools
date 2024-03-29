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

use context;
use context_system;
use core_table\local\filter\filter;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_cltools\local\crud\generic\generic_entity_table;
use local_cltools\local\filter\enhanced_filterset;
use local_cltools\local\table\dynamic_table_interface;
use local_cltools\local\table\dynamic_table_sql;
use ReflectionClass;
use UnexpectedValueException;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Helper
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper extends external_api {
    /**
     * Basic parameters for any query related to the table
     *
     * Note that we include filters as they can somewhat have an influcence on columns
     * selected too.
     *
     * @return external_function_parameters
     */
    public static function get_table_query_basic_parameters(): array {
        return array_merge(
                self::get_query_basic_parameters(), [
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
                                VALUE_DEFAULT,
                                []
                        ),
                        'filters' => new external_multiple_structure(
                                new external_single_structure([
                                        'type' => new external_value(PARAM_ALPHANUMEXT, 'Type of filter', VALUE_REQUIRED),
                                        'name' => new external_value(PARAM_ALPHANUM, 'Name of the filter', VALUE_REQUIRED),
                                        'jointype' => new external_value(PARAM_INT, 'Type of join for filter values',
                                                VALUE_REQUIRED),
                                        'required' => new external_value(PARAM_BOOL, 'Is this a required filter', VALUE_OPTIONAL,
                                                false),
                                        'values' => new external_multiple_structure(
                                                new external_value(PARAM_RAW, 'Filter value'),
                                                'The value to filter on',
                                                VALUE_REQUIRED
                                        )
                                ]),
                                'The filters that will be applied in the request',
                                VALUE_DEFAULT,
                                []
                        ),
                        'jointype' => new external_value(PARAM_INT, 'Type of join to join all filters together', VALUE_DEFAULT,
                                filter::JOINTYPE_NONE),
                        'editable' => new external_value(PARAM_BOOL, 'Is table editable ?', VALUE_DEFAULT, false),
                ]
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
    public static function get_query_basic_parameters(): array {
        return [
                'handler' => new external_value(
                // Note: We do not have a PARAM_CLASSNAME which would have been ideal.
                // For now we will have to check manually.
                        PARAM_RAW,
                        'Handler',
                        VALUE_REQUIRED
                ),
                'handlerparams' => new external_value(
                // Note: We do not have a PARAM_CLASSNAME which would have been ideal.
                // For now we will have to check manually.
                        PARAM_RAW,
                        'Handler parameters',
                        VALUE_DEFAULT,
                        ''
                ),
                'uniqueid' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'Unique ID for the container',
                        VALUE_REQUIRED
                ),
        ];
    }

    /**
     * Get table handler instance
     *
     * @param $handler
     * @param $uniqueid
     * @param bool $editable
     * @return mixed
     */
    public static function get_table_handler_instance($handler, $handlerparams, $uniqueid, $editable = false) {
        global $CFG;

        // Hack alert: this is to make sure we can "see" the test entities class.
        // We will need to think of a better approach.
        // Only run through behat or if we are in debug mode.
        if (debugging() || (defined('PHPUNIT_TEST') && PHPUNIT_TEST) || defined('BEHAT_SITE_RUNNING')) {
            require_once($CFG->dirroot . '/local/cltools/tests/lib.php');
        }

        if (!class_exists($handler)) {
            throw new UnexpectedValueException("Table handler class {$handler} not found. " .
                    "Please make sure that your handler is defined.");
        }

        if (!is_subclass_of($handler, dynamic_table_interface::class)) {
            throw new UnexpectedValueException("Table handler class {$handler} does not support dynamic updating.");
        }
        $classfilepath = (new ReflectionClass($handler))->getFileName();
        if (strpos($classfilepath, $CFG->dirroot) !== 0) {
            throw new UnexpectedValueException("Table handler class {$handler} must be defined in
                         {$CFG->dirroot}, instead of {$classfilepath}.");
        }
        if ($handler == generic_entity_table::class || is_subclass_of($handler, generic_entity_table::class) ) {
            $instance = new $handler($uniqueid, null, $editable, $handlerparams);
        } else {
            $instance = new $handler($uniqueid, null, $editable);
        }
        return $instance;
    }

    /**
     * Setup filters
     *
     * @param $instance
     * @param $filters
     * @param $jointype
     */
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
            $filterset = new enhanced_filterset($filtertypedef);
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

        /* @var dynamic_table_sql $instance dynamic table */
        $instance->set_filterset($filterset);
    }

    /**
     * Get current context or global system context
     *
     * @return context|context_system
     */
    public static function get_current_context() {
        global $PAGE;
        $context = null;
        try {
            $context = $PAGE->context;
            return $context;
        } finally {
            if (empty($context)) {
                $context = context_system::instance();
            }
            return $context;
        }
    }
}

