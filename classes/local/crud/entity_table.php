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

use coding_exception;
use core\persistent;
use dml_exception;
use html_writer;
use local_cltools\local\field\hidden;
use local_cltools\local\table\dynamic_table_sql;
use moodle_exception;
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
     * Sets up the page_table parameters.
     *
     * @throws coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid = null,
            $actionsdefs = null,
            $editable = false,
            $persistentclassname = null
    ) {
        parent::__construct($uniqueid, $actionsdefs, $editable);
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
     * Set the value of a specific row.
     *
     * @param $rowid
     * @param $fieldname
     * @param $newvalue
     * @param $oldvalue
     * @return void
     *
     *
     */
    public function set_value($rowid, $fieldname, $newvalue, $oldvalue) {
        /* @var $entity persistent the persistent class */
        $persistentclass = $this->define_class();
        $entity = new $persistentclass($rowid);
        $field = null;
        foreach ($this->fields as $f) {
            if ($f->get_name() == $fieldname) {
                $field = $f;
            }
        }
        if (empty($field)) {
            throw new moodle_exception('fielddoesnotexist', 'local_cltools', null, $fieldname);
        }
        if (empty($field) || !$field->is_editable()) {
            throw new moodle_exception('cannoteditfield', 'local_cltools', null, $field->get_display_name());
        }
        $entity->set($fieldname, $newvalue);
        $entity->update();
    }

    /**
     * Can be overriden
     *
     * @return string|null
     */
    public function define_class() {
        return static::$persistentclass;
    }
    /**
     * Get sql fields
     *
     * Overridable sql query
     *
     * @param string $tablealias
     */
    protected function internal_get_sql_fields($tablealias = 'e') {
        return parent::internal_get_sql_fields('entity');
    }
    /**
     * Overridable sql query
     */
    protected function internal_get_sql_from($tablealias = 'entity') {
        $persistentclass = $this->define_class();
        $from = $persistentclass::TABLE;
        $from = '{' . $from . '} entity';
        // Add joins.
        foreach ($this->fields as $field) {
            $additionalfrom = $field->get_additional_from('entity');
            $from .= " " . $additionalfrom;
        }
        return $from;
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
        $this->fields = entity_utils::get_defined_fields($this->define_class());
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
