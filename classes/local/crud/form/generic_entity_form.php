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

namespace local_cltools\local\crud\form;
/**
 * Persistent generic entity form
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generic_entity_form extends entity_form {
    /**
     * @var string $genericpersistentclass entity class name
     */
    protected static $persistentclass;

    /**
     * Constructor
     *
     * @param string $entityclassname
     * @param mixed $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param mixed $attributes
     * @param bool $editable
     * @param array $ajaxformdata
     */
    public function __construct($entityclassname, $action = null, $customdata = null, $method = 'post', $target = '',
            $attributes = null,
            $editable = true, $ajaxformdata = null) {
        self::$persistentclass = $entityclassname;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }
}
