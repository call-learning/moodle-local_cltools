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
 * Cltools services
 *
 * @package     local_cltools
 * @category    services
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
        'cltools_dynamic_table_get_rows' => [
                'classname' => 'local_cltools\\local\\table\\external\\get_rows',
                'methodname' => 'execute',
                'description' => 'Get rows from a table',
                'type' => 'read',
                'capabilities' => 'local/cltools:dynamictableread',
                'loginrequired' => true,
                'ajax' => true
        ],
        'cltools_dynamic_table_get_columns' => [
                'classname' => 'local_cltools\\local\\table\\external\\get_columns',
                'methodname' => 'execute',
                'description' => 'Get columns definition for a table',
                'type' => 'read',
                'capabilities' => 'local/cltools:dynamictableread',
                'loginrequired' => true,
                'ajax' => true
        ],
        'cltools_dynamic_table_set_value' => [
                'classname' => 'local_cltools\\local\\table\\external\\set_value',
                'methodname' => 'execute',
                'description' => 'Set a specific row/column value',
                'type' => 'write',
                'capabilities' => 'local/cltools:dynamictablewrite',
                'loginrequired' => true,
                'ajax' => true
        ],
        'cltools_dynamic_table_is_value_valid' => [
                'classname' => 'local_cltools\\local\\table\\external\\validate_value',
                'methodname' => 'execute',
                'description' => 'Set a specific row/column value',
                'type' => 'read',
                'capabilities' => 'local/cltools:dynamictableread',
                'loginrequired' => true,
                'ajax' => true
        ],

        'cltools_entity_lookup' => [
                'classname' => 'local_cltools\\local\\table\\external\\entity_lookup',
                'methodname' => 'execute',
                'description' => 'Get an associative array of identifier and display parameters',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'local/cltools:entitylookup',
                'loginrequired' => true
        ],
        'cltools_generic_lookup' => [
                'classname' => 'local_cltools\\local\\table\\external\\generic_lookup',
                'methodname' => 'execute',
                'description' => 'Get an associative array of identifier and username or coursename',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'local/cltools:entitylookup,',
                'loginrequired' => true
        ],
);
