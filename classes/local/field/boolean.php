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

use coding_exception;

/**
 * Boolean field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class boolean extends persistent_field {
    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_BOOL,
                'default' => false
        ];
        $this->init($fielnameordef, $standarddefaults);
    }

    /**
     * Get the matching formatter type to be used for display
     *
     * @return string|null return the type (and null if no formatter)
     *
     */
    public function get_column_formatter() {
        $format = parent::get_column_formatter();
        $format->formatter = 'tickCross';
        $format->formatterParams = (object) [
                'allowEmpty' => true,
                'allowTruthy' => false
        ];
        return $format;
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @return string|null return the type (and null if no filter)
     *
     */
    public function get_column_editor() {
        return (object) [
                'editor' => 'tickCross',
                'editorParams' => (object) [
                        'indeterminateValue' => get_string('notavailable', 'local_cltools'),
                        'allowEmpty' => true,
                        'allowTruthy' => true,
                        'tristate' => false
                ]
        ];
    }

    /**
     * Return a printable version of the current value
     *
     * @param bool $value
     * @param mixed $additionalcontext
     * @return mixed
     */
    public function format_value($value, $additionalcontext = null) {
        return $value ? get_string('truevalue', 'local_cltools')
                : get_string('falsevalue', 'local_cltools');
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @throws field_exception
     */
    public function validate_value($value) {
        $value = strtolower($value);
        if (!$this->is_true_value($value) && !$this->is_false_value($value)) {
            throw new field_exception('invalidvalue', $value);
        }
        return true;
    }

    /**
     * Return true if the value is truish
     *
     * @param mixed $value
     * @return bool
     * @throws coding_exception
     */
    protected function is_true_value($value) {
        return in_array($value, [
                get_string('truevalue', 'local_cltools'),
                true,
                1,
                'true',
                '1'
        ], true);
    }

    /**
     * Return false if the value is falsish
     *
     * @param mixed $value
     * @return bool
     * @throws coding_exception
     */
    protected function is_false_value($value) {
        return in_array($value, [
                get_string('falsevalue', 'local_cltools'),
                false,
                0,
                'false',
                '0'
        ], true);
    }
    /**
     * Get form field type
     * @return string
     */
    public function get_form_field_type() {
        return "advcheckbox";
    }
}
