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

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Get columns
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_columns extends external_api {
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
    public static function execute(
            string $handler,
            string $handlerparams,
            string $uniqueid,
            ?array $filters = null,
            ?string $jointype = null,
            ?bool $editable = null
    ) {
        [
                'handler' => $handler,
                'handlerparams' => $handlerparams,
                'uniqueid' => $uniqueid,
                'filters' => $filters,
                'jointype' => $jointype,
                'editable' => $editable,
        ] = self::validate_parameters(self::execute_parameters(), [
                'handler' => $handler,
                'handlerparams' => $handlerparams,
                'uniqueid' => $uniqueid,
                'filters' => $filters,
                'jointype' => $jointype,
                'editable' => $editable,
        ]);

        $instance = helper::get_table_handler_instance($handler, $handlerparams, $uniqueid, $editable);
        $instance->validate_access();
        helper::setup_filters($instance, $filters, $jointype);
        /* @var $instance dynamic_table_sql instance */
        $columndefs = array_values($instance->get_fields_definition());

        return $columndefs;
    }

    /**
     * Describes the parameters for fetching the table html.
     *
     * @return external_function_parameters
     * @since Moodle 3.9
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
                helper::get_table_query_basic_parameters()
        );
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_multiple_structure
     * @since Moodle 3.9
     */
    public static function execute_returns(): external_multiple_structure {
        return
                new external_multiple_structure(
                        new external_single_structure([
                                'title' => new external_value(PARAM_TEXT, 'Column title.'),
                                'field' => new external_value(PARAM_TEXT, 'Field name in reference to this title.'),
                                'visible' => new external_value(PARAM_BOOL, 'Is visible ?.'),
                                'filter' => new external_value(PARAM_RAW, 'Filter: image, html, datetime ....', VALUE_OPTIONAL),
                                'filterParams' => new external_value(PARAM_RAW, 'Filter parameter as JSON, ....', VALUE_OPTIONAL),
                                'formatter' => new external_value(PARAM_RAW, 'Formatter: image, html, datetime ....',
                                        VALUE_OPTIONAL),
                                'formatterParams' => new external_value(PARAM_RAW, 'Formatter parameter as JSON, ....',
                                        VALUE_OPTIONAL),
                                'editor' => new external_value(PARAM_RAW, 'Editor: image, html, datetime ....', VALUE_OPTIONAL),
                                'editorParams' => new external_value(PARAM_RAW, 'Editor: parameter as JSON, ....', VALUE_OPTIONAL),
                                'validator' => new external_value(PARAM_RAW, 'Validator: image, html, datetime ....',
                                        VALUE_OPTIONAL),
                                'validatorParams' => new external_value(PARAM_RAW, 'Validator: parameter as JSON, ....',
                                        VALUE_OPTIONAL),
                                'additionalParams' => new external_value(PARAM_RAW, 'Additional params in the form of a JSON object,
                      will be merged with column definition', VALUE_OPTIONAL),
                        ])
                );
    }
}
