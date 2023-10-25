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
namespace local_cltools\local\crud;

use context;
use core\persistent;
use html_writer;
use local_cltools\local\table\dynamic_table_sql;
use moodle_exception;

/**
 * Persistent table base class
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
     * Define properties (additional properties)
     *
     * @return array
     */
    protected static function define_column_order() {
        return [];
    }

    /**
     * Define properties (additional properties)
     *
     * @return array
     */
    protected static function define_properties() {
        return [];
    }

    /**
     * Set the value of a specific row.
     *
     * @param int $rowid
     * @param string $fieldname
     * @param mixed $newvalue
     * @param mixed $oldvalue
     * @return void
     */
    public function set_value($rowid, $fieldname, $newvalue, $oldvalue): void {
        $entity = $this->get_entity_helper($rowid);
        $field = null;
        foreach ($this->fields as $f) {
            if ($f->get_name() == $fieldname) {
                $field = $f;
            }
        }
        if (empty($field)) {
            throw new moodle_exception('fielddoesnotexist', 'local_cltools', null, $fieldname);
        } else {
            if (!$field->can_edit()) {
                throw new moodle_exception('cannoteditfield', 'local_cltools', null, $field->get_display_name());
            }
            $entity->set($fieldname, $newvalue);
            $entity->update();
        }
    }

    /**
     * Entity retriever helper
     *
     * @param int $rowid
     * @return persistent
     * @throws \required_capability_exception
     */
    private function get_entity_helper($rowid): persistent {
        /* @var $entity persistent the persistent class */
        $persistentclass = $this->define_class();
        $entity = new $persistentclass($rowid);
        if ($entity instanceof enhanced_persistent) {
            $context = $entity->get_context();
        } else {
            $context = \context_system::instance();
        }
        if (!isloggedin() || !has_capability('local/cltools:dynamictablewrite', $context)) {
            throw new \required_capability_exception($context,
                    'local/cltools:dynamictablewrite', 'cannotsetvalue', 'local_cltools');
        }
        return $entity;
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
     * Check if the value is valid for this row, column
     *
     * @param int $rowid
     * @param string $fieldname
     * @param mixed $newvalue
     * @return bool
     */
    public function is_valid_value(int $rowid, string $fieldname, $newvalue): bool {
        try {
            $entity = $this->get_entity_helper($rowid);
            $entity->set($fieldname, $newvalue);
            return $entity->is_valid();
        } catch (moodle_exception $e) {
            return false;
        }
    }

    /**
     * Overridable sql query
     *
     * @param string $tablealias
     */
    protected function internal_get_sql_from($tablealias = 'e') {
        $persistentclass = $this->define_class();
        $from = $persistentclass::TABLE;
        $from = '{' . $from . '} ' . $tablealias;
        // Add joins.
        foreach ($this->fields as $field) {
            $additionalfrom = $field->get_additional_from($tablealias);
            $from .= " " . $additionalfrom;
        }
        return $from;
    }

    /**
     * Default property definition
     *
     * Add all the fields from persistent class except the reserved ones
     */
    protected function setup_fields() {
        $this->fields = entity_utils::get_defined_fields($this->define_class());
    }

    /**
     * Utility to get the relevant files for a given entity
     *
     * @param object $entity
     * @param string $entityfilearea
     * @param string $entityfilecomponent
     * @param string $altmessage
     * @return string
     */
    protected function internal_col_files($entity, $entityfilearea, $entityfilecomponent, $altmessage = 'entity-image') {
        $imagesurls = entity_utils::get_files_urls(
                $entity->id,
                $entityfilearea,
                $entityfilecomponent);
        $imageshtml = '';
        foreach ($imagesurls as $src) {
            $imageshtml .= html_writer::img($src, $altmessage, ['class' => 'img-thumbnail']);
        }
        return $imageshtml;
    }

    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     *
     * @param context $context
     * @param bool $writeaccess
     */
    public static function validate_access(context $context, bool $writeaccess = false): bool {
        if ($writeaccess) {
            return has_capability('local/cltools:dynamictablewrite', $context);
        }
        return has_capability('local/cltools:dynamictableread', $context);
    }
}
