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
use core\persistent;
use renderer_base;

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
     * @return object|null return the type (and null if no formatter)
     *
     */
    public function get_column_formatter(): ?object {
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
     * @return object|null return the editor parameters (and null if no filter)
     *
     */
    public function get_column_editor(): ?object {
        return (object) [
                'editor' => 'tickCross',
                'editorParams' => (object) [
                        'indeterminateValue' => get_string('notavailable', 'local_cltools'),
                        'allowEmpty' => true,
                        'allowTruthy' => true,
                ]
        ];
    }

    /**
     * Filter must be 3 states
     *
     * @return object
     * @throws coding_exception
     */
    public function get_column_filter(): object {
        return (object) [
                'filter' => 'tickCross',
                'filterParams' => (object) [
                        'indeterminateValue' => get_string('notavailable', 'local_cltools'),
                        'allowEmpty' => true,
                        'allowTruthy' => true,
                        'tristate' => true
                ]
        ];
    }

    /**
     * Return a printable version of the current value
     *
     * @param persistent|null $persistent
     * @param renderer_base|null $renderer
     * @return string
     */
    public function format_value(?persistent $persistent = null, ?renderer_base $renderer = null): string {
        $value = parent::format_value($persistent, $renderer);
        return $value ? get_string('truevalue', 'local_cltools')
                : get_string('falsevalue', 'local_cltools');
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @throws field_exception
     * @return bool
     */
    public function validate_value($value): bool {
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
     */
    protected function is_true_value($value): bool {
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
     */
    protected function is_false_value($value): bool {
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
     *
     * @return string
     */
    public function get_form_field_type():string {
        return "advcheckbox";
    }
}
