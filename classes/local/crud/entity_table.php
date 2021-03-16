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

use context;
use html_writer;
use local_cltools\local\field\base;
use local_cltools\local\field\html;
use local_cltools\local\table\dynamic_table_sql;
use moodle_url;
use pix_icon;
use popup_action;

/**
 * Persistent list base class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_table extends dynamic_table_sql {

    protected static $persistentclass = null;

    public function __construct($uniqueid, $actionsdefs = null, $editable = false) {
        parent::__construct($uniqueid, $actionsdefs, $editable);
    }

    /**
     * Set SQL parameters (where, from,....) from the entity
     *
     * This can be overridden when we are looking at linked entities.
     */
    protected function set_initial_sql() {
        $sqlfields = forward_static_call([static::$persistentclass, 'get_sql_fields'], 'entity', '');
        $from = static::$persistentclass::TABLE;
        $this->set_sql($sqlfields, '{' . $from . '} entity', '1=1', []);
    }

    /**
     * Default property definition
     *
     * Add all the fields from persistent class except the reserved ones
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function setup_fields() {
        $this->fields = [];
        foreach (static::$persistentclass::properties_definition() as $name => $prop) {
            if (entity_utils::is_reserved_property($name) || !(empty($existingproperties[$name]))) {
                $prop['fullname'] = $name;
                $prop['fieldname'] = $name;
                $prop['format'] = [
                    'type' => 'hidden'
                ];
            } else {
                $prop['fullname'] = entity_utils::get_string_for_entity(static::$persistentclass, $name);
                $prop['fieldname'] = $name;
            }
            $this->fields[$name] = base::get_instance_from_persistent_def($name, $prop);
        }
        $this->setup_other_fields();
    }

    /**
     * Format the actions cell.
     *
     * @param $row
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function col_actions($row) {
        global $OUTPUT;
        $actions = [];
        foreach ($this->actionsdefs as $k => $a) {
            $url = new moodle_url($a->url, ['id' => $row->id]);
            $popupaction = empty($a->popup) ? null :
                new popup_action('click', $url);
            $actions[] = $OUTPUT->action_icon(
                $url,
                new pix_icon($a->icon,
                    get_string($k, 'local_cltools')),
                $popupaction
            );
        }

        return implode('&nbsp;', $actions);
    }

    /**
     * Utility to get the relevant files for a given entity
     *
     * @param object $entity
     * @return string
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function internal_col_files($entity, $entityfilearea, $entityfilecomponent, $altmessage = 'entity-image') {
        $imagesurls = entity_utils::get_files_urls(
            $entity->id,
            $entityfilearea,
            $entityfilecomponent);
        $imageshtml = '';
        foreach ($imagesurls as $src) {
            $imageshtml .= \html_writer::img($src, $altmessage, array('class' => 'img-thumbnail'));
        }
        return $imageshtml;
    }
}
