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
use local_cltools\local\crud\entity_utils;
use local_cltools\local\field\entity_selector;
use moodle_exception;
use restricted_context_exception;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Entity lookup
 *
 * This is basically for Moodle 3.9 very similar to dynamic table but with
 * tabulator.js library in mind and compatibility with persistent entities
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_lookup extends external_api {
    /**
     * Entity lookup value
     *
     * @param string $entityclass persistent class name.
     * @param string $displayfield display field
     * @return array
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function execute(string $entityclass, string $displayfield) {
        [
                'entityclass' => $entityclass,
                'displayfield' => $displayfield,
        ] = self::validate_parameters(self::execute_parameters(), [
                'entityclass' => $entityclass,
                'displayfield' => $displayfield,
        ]);
        $values = [
                [
                        'id' => 0,
                        'value' => get_string('notavailable', 'local_cltools')
                ]
        ];
        $warnings = [];
        $context = helper::get_current_context();
        self::validate_context($context);
        // If entity class has a validate_access() method, then use it.
        if (!entity_utils::validate_entity_access($entityclass, $context)) {
            throw new restricted_context_exception();
        }
        try {
            $values = array_merge($values, entity_selector::entity_lookup($entityclass, $displayfield));
        } catch (moodle_exception $e) {
            $warnings[] = (object) [
                    'item' => $entityclass,
                    'itemid' => 0,
                    'displayfield' => $displayfield,
                    'warningcode' => "lookuperror",
                    'message' => "Lookup error for entity $entityclass: {$e->getMessage()} and $displayfield."
            ];
        }
        return [
                'values' => $values,
                'warnings' => $warnings
        ];
    }

    /**
     * Entity lookup value parameters
     */
    public static function execute_parameters() {
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
                                VALUE_DEFAULT,
                                ''
                        )
                ]
        );

    }

    /**
     * Entity lookup value returns
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
                array(
                        'values' => new \external_multiple_structure(
                                new external_single_structure(
                                        [
                                                'id' => new external_value(PARAM_INT, 'entity id'),
                                                'value' => new external_value(PARAM_RAW, 'display value'),
                                        ]
                                )
                        ),
                        'warnings' => new external_warnings()
                )
        );
    }

}

