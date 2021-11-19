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
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();
/**
 * Date field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date extends persistent_field {
    /**
     * Construct the field from its definition
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
            'required' => false,
            'rawtype' => PARAM_INT,
            'default' => time()
        ];
        $this->init($fielnameordef, $standarddefaults);
    }

    /**
     * Form field type for this field, used in default implementation of form_add_element
     */
    const FORM_FIELD_TYPE = 'date_selector';

    /**
     * Get the matching formatter type to be used for display
     *
     * @return string|null return the type (and null if no formatter)
     *
     * @throws coding_exception
     */
    public function get_column_formatter() {
        $format = parent::get_column_formatter();
        $format->formatter = 'datets';
        $format->formatterParams = (object) [
            'formatter' => 'datets',
            'formatterParams' => (object) [
                'outputFormat' => get_string('momentjsdateformat', 'local_cltools'),
                'locale' => current_language(),
                'timezone' => usertimezone()
            ]
        ];
        return $format;
    }

    /**
     * Return a printable version of the current value
     *
     * @param int $value
     * @param mixed $additionalcontext
     * @return mixed
     */
    public function format_value($value, $additionalcontext = null) {
        return userdate($value, get_string('strftimedatefullshort', 'langconfig'));;
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @throws field_exception
     */
    public function validate_value($value) {
        if (strtotime($value) === false) {
            throw new field_exception('valuecannotbechecked', $value);
        }
        return true;
    }
}
