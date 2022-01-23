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
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use moodle_exception;
use ReflectionException;
use restricted_context_exception;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Validation check
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_value extends external_api {
    /**
     * Set a field value
     *
     * @param string $handler Dynamic table class name.
     * @param string $handlerparams Handler params
     * @param string $uniqueid Unique ID for the container.
     * @param string $field fieldname
     * @param string $value value
     * @return array
     * @throws ReflectionException
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function execute(
            string $handler,
            string $handlerparams,
            string $uniqueid,
            int $id,
            string $field,
            string $value) {
        [
                'handler' => $handler,
                'handlerparams' => $handlerparams,
                'uniqueid' => $uniqueid,
                'id' => $id,
                'field' => $field,
                'value' => $value,
        ] = self::validate_parameters(self::execute_parameters(), [
                'handler' => $handler,
                'handlerparams' => $handlerparams,
                'uniqueid' => $uniqueid,
                'id' => $id,
                'field' => $field,
                'value' => $value
        ]);

        $instance = helper::get_table_handler_instance($handler, $handlerparams, $uniqueid, true);
        $instance->validate_access();
        $success = false;
        $warnings = array();
        try {
            $success = $instance->is_valid_value($id, $field, json_decode($value));
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
    public static function execute_parameters() {
        return new external_function_parameters (
                array_merge(helper::get_query_basic_parameters(),
                        [
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
                )
        );
    }

    /**
     * Set value returns
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
                array(
                        'success' => new external_value(PARAM_BOOL, 'True if the value was updated, false otherwise.'),
                        'warnings' => new external_warnings()
                )
        );
    }
}

