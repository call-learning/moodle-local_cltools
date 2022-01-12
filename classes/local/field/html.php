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
 * Full html text field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html extends persistent_field {
    /**
     * Form field type for this field, used in default implementation of form_add_element
     */
    const FORM_FIELD_TYPE = 'editor';

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_RAW,
                'default' => ''
        ];
        $this->init($fielnameordef, $standarddefaults);
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function get_column_formatter() {
        $format = parent::get_column_formatter();
        $format->formatter = 'html';
        return $format;
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform, ...$additionalargs) {
        $elementname = $this->get_name() . '_editor';
        $mform->addElement(static::FORM_FIELD_TYPE, $elementname, $this->get_display_name());
        parent::internal_form_add_element($mform, $elementname);
        $mform->setType($this->get_name(), PARAM_RAW);
    }
}
