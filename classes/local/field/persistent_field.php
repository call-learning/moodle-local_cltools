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

namespace local_cltools\local\field;

use local_cltools\local\field\adapter\form_adapter_default;
use local_cltools\local\field\adapter\tabulator_default_trait;

defined('MOODLE_INTERNAL') || die();

/**
 * Base field
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_field {
    use tabulator_default_trait;
    use form_adapter_default;

    /**
     * Form field type for this field
     */
    const FORM_FIELD_TYPE = 'text';
    /**
     * @var bool $required
     */
    protected $required = false;
    /**
     * @var null $default
     */
    protected $default = null;
    /**
     * @var string $fullname
     */
    protected $fullname = null;
    /**
     * @var string $fieldname
     */
    protected $fieldname = null;
    /**
     * @var bool $visible
     */
    protected $visible = false;
    /**
     * @var bool $sortable
     */
    protected bool $sortable;
    /**
     * @var bool $editable
     */
    protected $editable = false;

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
     * Get the display name for this field
     *
     * @return mixed
     */
    public function get_display_name() {
        return $this->fullname;
    }

    /**
     * Check if the field is visible or not
     *
     * @return boolean visibility
     *
     */
    public function is_visible() {
        return $this->visible;
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @throws field_exception
     */
    public function validate_value($value) {
        throw new field_exception('valuecannotbechecked', $value);
    }

    /**
     * Return a printable version of the value provided in input
     *
     * @param $value
     * @param mixed $additionalcontext
     * @return mixed
     */
    public function format_value($value, $additionalcontext = null) {
        return $value;
    }

    /**
     * Callback for this field, so data can be converted before sending it to a persistent
     *
     * @param $data
     */
    public function form_filter_data(&$itemdata, ...$args) {

    }

    /**
     * Get addional joins
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return array
     */
    public function get_additional_sql($entityalias) {
        return ["", ""];
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

    /**
     * Get persistent properties
     *
     * @return array[]
     */
    public function get_persistent_properties(): array {
        $property = [];
        $property['type'] = $this->get_raw_param_type();
        $property['null'] = $this->is_required();
        if (!$this->is_required()) {
            $property['default'] = $this->get_default();
        }

        return [$this->get_name() => $property];
    }

    /**
     * Call
     */

    /**
     * Get the type of parameter (see PARAM_XXX) for this type
     *
     * @return mixed
     */
    public function get_raw_param_type() {
        return $this->rawtype;
    }

    /**
     * Check if the field is required or not
     *
     * @return boolean required
     *
     */
    public function is_required() {
        return $this->required;
    }

    /**
     * Get the display name for this field
     *
     * @return mixed
     */
    public function get_default() {
        return $this->default;
    }

    /**
     * Get the field name for this field
     *
     * @return mixed
     */
    public function get_name() {
        return $this->fieldname;
    }

    /**
     * Can we sort the column ?
     *
     * @return bool
     */
    public function can_sort() {
        return $this->sortable;
    }

    /**
     * Is the field editable
     *
     * @return bool
     */
    public function is_editable() {
        return $this->editable;
    }

    /**
     * Initialise
     *
     * @param string|array $fielddef there is a shortform with defaults for boolean field and a long form with all or a partial
     * @param array $defaultvalues standard values for this field (default)
     * @return object
     */
    protected function init($fielddef, array $defaultvalues) {
        if (is_string($fielddef)) {
            $fielddef = [
                    'fieldname' => $fielddef
            ];
        }
        // Get default values
        $fielddef = array_merge($defaultvalues, $fielddef);
        $fielddef = (object) $fielddef;
        $this->required = !isset($fielddef->required) ? false : $fielddef->required;
        $this->default = !isset($fielddef->default) ? '' : $fielddef->default;
        $this->rawtype = empty($fielddef->rawtype) ? PARAM_RAW : $fielddef->rawtype;
        $this->fieldname = $fielddef->fieldname;
        $this->fullname = empty($fielddef->fullname) ? $this->fieldname : $fielddef->fullname;
        $this->visible = !isset($fielddef->visible) ? true : $fielddef->visible;
        $this->sortable = !isset($fielddef->sortable) ? true : $fielddef->sortable;
        $this->editable = !isset($fielddef->editable) ? false : $fielddef->editable;
        return $fielddef;
    }
}
