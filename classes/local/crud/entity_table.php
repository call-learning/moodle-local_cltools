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
 * Persistent object list
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\invalid_persistent_exception;
use core\persistent;
use dml_exception;
use html_writer;
use local_cltools\local\table\dynamic_table_sql;
use moodle_exception;
use moodle_url;
use pix_icon;
use popup_action;
use ReflectionException;

/**
 * Persistent list base class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_table extends dynamic_table_sql {
    /**
     * @var string $persistentclass
     */
    protected static $persistentclass = null;

    /**
     * Set the value of a specific row.
     *
     * @param $rowid
     * @param $fieldname
     * @param $newvalue
     * @param $oldvalue
     * @return bool
     */
    public function set_value($rowid, $fieldname, $newvalue, $oldvalue) {
        /* @var $entity persistent the persistent class */
        $entity = new static::$persistentclass($rowid);
        try {
            $entity->set($fieldname, $newvalue);
            $entity->update();
            return true;
        } catch (invalid_persistent_exception $e) {
            return false;
        }

    }

    /**
     * Set SQL parameters (where, from,....) from the entity
     *
     * This can be overridden when we are looking at linked entities.
     */
    protected function set_initial_sql() {
        $sqlfields = forward_static_call([static::$persistentclass, 'get_sql_fields'], 'entity', '');
        $from = static::$persistentclass::TABLE;
        $from = '{' . $from . '} entity';
        // Add joins.
        // Set sorts (additional column).
        foreach ($this->fields as $field) {
            list($fields, $additionalfrom) = $field->get_additional_sql('entity');
            $from .= " " . $additionalfrom;
            if ($fields) {
                $sqlfields .= (!empty($sqlfields) ? ', ' : '') . $fields;
            }
            $this->fieldaliases[$field->get_name()] = "entity.{$field->get_name()}";
        }
        $this->set_sql($sqlfields, $from, '1=1', []);

    }

    /**
     * Default property definition
     *
     * Add all the fields from persistent class except the reserved ones
     *
     * @return array
     * @throws ReflectionException
     */
    protected function setup_fields() {
        $this->fields = entity_utils::get_defined_fields(static::$persistentclass);
    }

    /**
     * Define properties (additional properties)
     *
     * @return array
     */
    protected static function define_column_order() {
        return array();
    }

    /**
     * Define properties (additional properties)
     *
     * @return array
     */
    protected static function define_properties() {
        return array();
    }

    /**
     * Utility to get the relevant files for a given entity
     *
     * @param object $entity
     * @return string
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function internal_col_files($entity, $entityfilearea, $entityfilecomponent, $altmessage = 'entity-image') {
        $imagesurls = entity_utils::get_files_urls(
            $entity->id,
            $entityfilearea,
            $entityfilecomponent);
        $imageshtml = '';
        foreach ($imagesurls as $src) {
            $imageshtml .= html_writer::img($src, $altmessage, array('class' => 'img-thumbnail'));
        }
        return $imageshtml;
    }


}
