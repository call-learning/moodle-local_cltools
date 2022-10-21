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
 * SQL Query adapter for field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\local\field\adapter;

/**
 * SQL Query adapter for field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface sql_query_adapter {
    /**
     * Get additional fields
     *
     * @param string $entityalias
     *
     * @return array of SQL fields (['xxx AS yyy', '...])
     */
    public function get_additional_fields(string $entityalias = 'e'): array;

    /**
     * Additional From Query
     *
     * @param string $entityalias
     *
     * @return string
     */
    public function get_additional_from(string $entityalias = 'e'): string;

    /**
     * Additional WHERE Query
     *
     * @param string $entityalias
     *
     * @return array ["WHERE QUERY",[params]]
     */
    public function get_additional_where(string $entityalias = 'e'): array;
}
