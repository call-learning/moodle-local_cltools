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
use MoodleQuickForm;
defined('MOODLE_INTERNAL') || die();
/**
 * Select field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class select_choice extends persistent_field {
    protected $choices = [];
    /**
     * Construct the field from its definition
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
            'required' => false,
            'rawtype' => PARAM_INT,
            'choices' => [],
            'default' => null
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        $this->choices = empty($fielddef->choices) ? [] : $fielddef->choices;
    }

    /**
     * Form field type for this field
     */
    const FORM_FIELD_TYPE = 'select';

    /**
     * Get the matching formatter type to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function get_column_formatter() {
        $format = parent::get_column_formatter();
        $format->formatter ='lookup';
        $format->formatterParams =  (object) $this->choices;
        return $format;
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
            'editor' => 'select',
            'editorParams' => (object) [
                'values' => (object) $this->choices
            ]
        ];
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link http://tabulator.info/docs/4.9/validate
     * @return object|null return the parameters (or null if no matching validator)
     *
     */
    public function get_column_validator() {
        return (object) [
            'validator' => "in:" . join('|', (array) array_keys($this->choices))
        ];
    }
    /**
     * Return a printable version of the current value
     *
     * @param int $value
     * @param mixed $additionalcontext
     * @return mixed
     */
    public function format_value($value, $additionalcontext = null) {
        return empty($this->choices[$value]) ? '': $this->choices[$value];
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform,  ...$additionalargs) {
        $mform->addElement('select',$this->get_name(), $this->get_display_name(), $this->choices);
        parent::internal_form_add_element($mform);
    }

}
