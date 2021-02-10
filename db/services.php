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
 * @category    upgrade
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'cltools_table_get_dynamic_table_content' => [
        'classname' => 'local_cltools\external\dynamic_table_get',
        'methodname' => 'execute',
        'description' => 'Get the dynamic table content raw html',
        'type' => 'read',
        'ajax' => true,
        // 'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
);
