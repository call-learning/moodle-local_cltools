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
namespace local_cltools\local;

use mysqli_native_moodle_database;
use oci_native_moodle_database;
use pgsql_native_moodle_database;
use sqlsrv_native_moodle_database;

/**
 * Data model utils
 *
 * Intended to retrofit some features found in 3.11
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dmlutils {
    /**
     * Group concat was introduced in 3.11 (https://tracker.moodle.org/browse/MDL-52817)
     * We need this to be able to backport this to < 3.11 release
     *
     * @param string $field
     * @param string $separator
     * @param string $sort
     * @return string
     */
    public static function get_sql_group_concat(string $field, string $separator = ', ', string $sort = ''): string {
        global $DB;
        if (method_exists($DB, 'sql_group_concat')) {
            return $DB->sql_group_concat($field, $separator, $sort);
        }
        // Yes, this is ugly, but as it will soon be deprecated as support shifts toward
        // 3.11 +, this is not worth really trying to spend much time on it.
        if (is_a($DB, mysqli_native_moodle_database::class)) {
            $fieldsort = $sort ? "ORDER BY {$sort}" : '';
            return "GROUP_CONCAT({$field} {$fieldsort} SEPARATOR '{$separator}')";
        }
        if (is_a($DB, oci_native_moodle_database::class)) {
            $fieldsort = $sort ?: '1';
            return "LISTAGG({$field}, '{$separator}') WITHIN GROUP (ORDER BY {$fieldsort})";
        }
        if (is_a($DB, pgsql_native_moodle_database::class)) {
            $fieldsort = $sort ? "ORDER BY {$sort}" : '';
            return "STRING_AGG(CAST({$field} AS VARCHAR), '{$separator}' {$fieldsort})";
        }
        if (is_a($DB, sqlsrv_native_moodle_database::class)) {
            $fieldsort = $sort ? "WITHIN GROUP (ORDER BY {$sort})" : '';
            return "STRING_AGG({$field}, '{$separator}') {$fieldsort}";
        }
        return '';
    }
}
