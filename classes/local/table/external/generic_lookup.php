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

use context_system;
use core\event\user_loggedin;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use local_cltools\local\field\generic_selector;
use restricted_context_exception;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Generic lookup
 *
 * User lookup: retrieve a list of users.
 * Course lookup (TODO): retrieve a list of users.
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generic_lookup extends external_api {
    /**
     * Entity lookup value
     *
     * @param string $type
     * @return array
     */
    public static function execute(string $type): array {
        [
                'type' => $type,
        ] = self::validate_parameters(self::execute_parameters(), [
                'type' => $type,
        ]);
        raise_memory_limit(MEMORY_HUGE);
        $context = isloggedin() ? context_system::instance() : null;
        self::validate_context($context);
        $values = generic_selector::get_generic_entities($type);
        return [
                'values' => $values,
        ];
    }

    /**
     * Entity lookup value parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters ([
                'type' => new external_value(
                        PARAM_ALPHANUMEXT,
                        'Type as user, course...',
                        VALUE_REQUIRED
                ),
        ]);

    }

    /**
     * Entity lookup value returns
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
                [
                        'values' => new \external_multiple_structure(
                                new external_single_structure(
                                        [
                                                'id' => new external_value(PARAM_INT, 'entity id'),
                                                'value' => new external_value(PARAM_RAW, 'display value'),
                                        ]
                                )
                        ),
                ]
        );
    }

}

