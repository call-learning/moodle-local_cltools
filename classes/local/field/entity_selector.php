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
 * Base field
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\field;
use local_cltools\local\crud\entity_utils;

defined('MOODLE_INTERNAL') || die();

class entity_selector extends base {
    protected $entityclass = "";
    protected $displayfield = "";


    public function __construct($fielddef) {
        parent::__construct($fielddef);
        if (is_array($fielddef)) {
            $fielddef = (object) $fielddef;
        }
        $this->entityclass = empty($fielddef->format['entityclass']) ? null : $fielddef->format['entityclass'];
        $this->displayfield = empty($fielddef->format['displayfield']) ? 'id' : $fielddef->format['displayfield'];
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @return mixed
     * @throws \dml_exception
     */
    public function internal_add_form_element(&$mform) {
        $choices = $this->get_entities();
        $mform->addElement('searchableselector', $this->fieldname, $this->fullname, $choices);
    }

    /**
     * Get the matching formatter type to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function get_column_formatter() {
        return (object) [
            'formatter' => 'entity_lookup',
            'formatterParams'  => (object) [
                'entityclass' => $this->entityclass,
                'displayfield' => $this->displayfield
            ],
        ];
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function get_column_editor() {
        return (object) [
            'editor' => 'entity_lookup',
            'editorParams' => (object) [
                'entityclass' => $this->entityclass,
                'displayfield' => $this->displayfield
            ]
        ];
    }
    /**
     * @return array
     * @throws \dml_exception
     */
    protected function get_entities() {
        static::entity_lookup($this->entityclass, $this->displayfield);
    }

    /**
     * Generic entity lookup.
     *
     * Used in external API.
     * @param $entityclass
     * @param $displayfield
     * @return array
     * @throws \dml_exception
     */
    public static function entity_lookup($entityclass, $displayfield) {
        global $DB;
        if ($entityclass && class_exists($entityclass)) {
            $allrecords = $DB->get_records_menu($entityclass::TABLE, null, "$displayfield ASC", 'id,' . $displayfield);
            $allrecords[0] = get_string('notavailable', 'local_cltools');
            return $allrecords;
        } else {
            return [];
        }
    }

    /**
     * Get addional joins and fields
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return string
     * @throws \ReflectionException
     */
    public function get_additional_sql($entityalias) {
        $table =  ($this->entityclass)::TABLE;
        $aliasname = entity_utils::get_persistent_prefix($this->entityclass);
        return  [
            "{$aliasname}.{$this->displayfield} AS {$aliasname}{$this->displayfield}",
            "LEFT JOIN {".$table."} $aliasname ON {$aliasname}.id = {$entityalias}.{$this->fieldname}"
            ];

    }


    /**
     * Get addional additional invisible sort field
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return string
     */
    public function get_additional_util_field() {
        $aliasname = entity_utils::get_persistent_prefix($this->entityclass);
        $fieldname = $aliasname.$this->displayfield;
        $field = base::get_instance_from_def($fieldname, [
            "fullname" => $fieldname,
            "rawtype" => PARAM_RAW,
            "type" => "hidden"
            ]
        );
        return $field;
    }
}