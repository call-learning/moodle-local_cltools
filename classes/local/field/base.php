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

defined('MOODLE_INTERNAL') || die();
abstract class base {

    protected $formatparameters = null;

    protected $required = false;

    protected $default = null;

    protected $fullname = null;

    protected $fieldname = null;

    public function __construct($fielddef)  {
        if (is_array($fielddef)) {
            $fielddef = (object) $fielddef;
        }
        $this->required = empty($fielddef->required) ? false: $fielddef->required;
        $this->default = empty($fielddef->default) ? '' : $fielddef->default;
        $this->rawtype = empty($fielddef->rawtype) ? PARAM_RAW : $fielddef->rawtype;
        $this->fieldname = $fielddef->fieldname;
        $this->fullname = empty($fielddef->fullname) ? $this->fieldname : $fielddef->fullname;
    }

    /**
     * Matches PARAM_XXX to field type
     */
    const MOODLE_PARAM_RAW_TO_TYPE = [
        PARAM_INT => 'number',
        PARAM_BOOL => 'boolean',
        PARAM_TEXT => 'text',
        PARAM_ALPHA => 'text',
        PARAM_ALPHANUMEXT => 'text',
        PARAM_ALPHANUM => 'text',
        PARAM_STRINGID => 'text',
        PARAM_EMAIL => 'text',
        PARAM_URL => 'text'
    ];
    /**
     *
     * Same as @static::get_instance_from_def
     *
     * @param array $fielddef associative array with information on how to build / setup the field
     * Mandatory fields are:
     * * rawtype: the raw type as PARAM_XXX
     * * required: (boolean) if this field is required or not
     * * default: default value
     * * fullname: full name (display name) of the field
     * * fieldname: field name as a shortname/id number
     * Optionally:
     * * type the desired type, if not it is inferred from rawtype
     * * choices: if a set of choices is given we setup a select_choice field
     *
     *
     *
     * @return static
     */
    public static function get_instance_from_persistent_def($name, $fielddef = []) {
        if (!empty($fielddef['format'])) {
           if (!empty($fielddef['format']['type'])) {
               $fielddef['type']  = $fielddef['format']['type'];
           }
            if (!empty($fielddef['format']['choices'])) {
                $fielddef['choices']  = $fielddef['format']['choices']; // We use the associative array instead.
            }
        } else {
            if (!empty(self::MOODLE_PARAM_RAW_TO_TYPE[$fielddef['type']])) {
                $fielddef['rawtype'] = $fielddef['type'];
                $fielddef['type'] = self::MOODLE_PARAM_RAW_TO_TYPE[$fielddef['type']];
            } else {
                $fielddef['rawtype'] = 'raw';
                $fielddef['type'] = 'text';
            }
        }
        return static::get_instance_from_def($name, $fielddef);
    }
    /**
     * @param array $fielddef associative array with information on how to build / setup the field
     * Mandatory fields are:
     * * rawtype: the raw type as PARAM_XXX
     * * required: (boolean) if this field is required or not
     * * default: default value
     * * fullname: full name (display name) of the field
     * * fieldname: field name as a shortname/id number
     * Optionally:
     * * type the desired type, if not it is inferred from rawtype
     * * choices: if a set of choices is given we setup a select_choice field
     *
     *
     *
     * @return static
     */
    public static function get_instance_from_def($name, $fielddef = []) {
        // PARAM_XXX
        if (empty($fielddef['type'])) {
            if (!empty(self::MOODLE_PARAM_RAW_TO_TYPE[$fielddef['rawtype']])) {
                $fielddef['type'] = self::MOODLE_PARAM_RAW_TO_TYPE[$fielddef['rawtype']];
            } else {
                $fielddef['type'] = 'text';
            }
        }
        $type = $fielddef['type'];
        if ($type != 'hidden' && !empty($fielddef['choices'])) {
            $type = "select_choice";
        }
        $classname = __NAMESPACE__.'\\'. $type;
        if (!class_exists($classname)) {
            $type = 'local_cltools\\local\\field\\text';
        }
        $fielddef['fieldname'] = $name;
        return new $classname($fielddef);
    }
    /**
     * Return a printable version of the current value
     * @param $value
     * @return mixed
     */
    public function format_string($value, $additionalcontext = null) {
        return $value;
    }

    /**
     * Get an identifier for this type of format
     *
     * @return mixed
     */
    public function get_type() {
        $tableclass = explode("\\", get_class($this));
        return end($tableclass);
    }

    /**
     * Get the type of parameter (see PARAM_XXX) for this type
     *
     * @return mixed
     */
    public function get_raw_param_type() {
        return $this->rawtype;
    }

    /**
     * Get the the display name of this
     *
     * @return mixed
     */
    public function get_display_name() {
        return $this->fullname;
    }

    /**
     * Get the matching filter type and parameters to be used for display
     *
     *
     * @link  http://tabulator.info/docs/4.9/filter
     * @return object|null return the parameters (or null if no matching filter)
     *
     */
    public function get_column_filter() {
        $editor = $this->get_column_editor();
        if ($editor) {
            return (object) [
                'filter' => $editor->editor,
                'filterParams' => $editor->editorParams
            ];
        }
        return null;
    }
    /**
     * Get the matching formatter type and parameters to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function get_column_formatter() {
        return null;
    }

    /**
     * Get the matching editor type and parameters to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function get_column_editor() {
        return null;
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link http://tabulator.info/docs/4.9/validate
     * @return object|null return the parameters (or null if no matching validator)
     *
     */
    public function get_column_validator() {
        return null;
    }

    /**
     * Check if the field is visible or not
     *
     * @return boolean visibility
     *
     */
    public function is_visible() {
        return true;
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @return string|null return the type (and null if no filter)
     *
     */
    public function is_valid($any) {
        return true;
    }

    public function get_field_name() {
        return $this->fieldname;
    }

    /**
     * Add element onto the form
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function add_form_element(&$mform) {
        $this->internal_add_form_element($mform);
        $mform->setType($this->fieldname, $this->get_raw_param_type());
        if ($this->required) {
            $mform->addRule($this->fieldname, get_string('required'), 'required');
        }
        if ($this->default) {
            $mform->setDefault($this->fieldname, $this->default);
        }
    }


    /**
     * Call
     */

    /**
     * Callback for this field, so data can be converted before sending it to a persistent
     * @param $data
     */
    public function filter_data_for_persistent(&$itemdata, ...$args) {

    }
    /**
     * Callback for this field, so data can be converted before form submission
     * @param $data
     */
    public function prepare_files(&$itemdata, ...$args) {
    }

    /**
     * Callback for this field, so data can be saved after form submission
     * @param $data
     */
    public function save_files(&$itemdata, ...$args) {
    }

    /**
     * Callback to actually add the form element to the form itself
     *
     * @param $mform
     * @return mixed
     */
    abstract protected function internal_add_form_element(&$mform);

    /**
     * Get addional joins
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return string
     */
    public function get_additional_sql($entityalias) {
        return ["",""];
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
        return null;
    }
}