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
 * Repository to perform WS calls for local_ctools.
 *
 * @module      local_cltools/table//repository
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';

/**
 * Get all rows from a dynamic table
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const getTableRows = args => fetchMany([{ methodname: 'cltools_dynamic_table_get_rows',args}])[0];

/**
 * Get all columns from a dynamic table
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const getTableColumns = args => fetchMany([{ methodname: 'cltools_dynamic_table_get_columns',args}])[0];

/**
 * Set a value from a table
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const setTableValue = args => fetchMany([{ methodname: 'cltools_dynamic_table_set_value',args}])[0];

/**
 * Check if a value is valid
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const validateTableValue = args => fetchMany([{ methodname: 'cltools_dynamic_table_validate_value',args}])[0];

/**
 * Lookup values for an entity
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const entityTableLookup = args => fetchMany([{ methodname: 'cltools_entity_lookup',args}])[0];

/**
 * Lookup values for generic moodle entities such as users or courses
 *
 * @param   {object} args
 * @returns {Promise}
 */
export const genericTableLookup = args => fetchMany([{ methodname: 'cltools_generic_lookup',args}])[0];
