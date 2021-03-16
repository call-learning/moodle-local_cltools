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
 * @copyright   2021 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'cltools_dynamic_table_get_rows' => [
        'classname' => 'local_cltools\\local\\table\\external',
        'methodname' => 'get_rows',
        'description' => 'Get rows from a table',
        'type' => 'read',
        'ajax' => true
    ],
    'cltools_dynamic_table_get_columns' => [
        'classname' => 'local_cltools\\local\\table\\external',
        'methodname' => 'get_columns',
        'description' => 'Get columns definition for a table',
        'type' => 'read',
        'ajax' => true
    ],
    'cltools_dynamic_table_set_value' => [
        'classname' => 'local_cltools\\local\\table\\external',
        'methodname' => 'set_value',
        'description' => 'Set a specific row/column value',
        'type' => 'write',
        'ajax' => true
    ],
    'cltools_dynamic_table_is_value_valid' => [
        'classname' => 'local_cltools\\local\\table\\external',
        'methodname' => 'is_value_valid',
        'description' => 'Set a specific row/column value',
        'type' => 'read',
        'ajax' => true
    ]

);
