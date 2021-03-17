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
 * Base formatter
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\field;
defined('MOODLE_INTERNAL') || die();

class text extends base {
    /**
     * Add element onto the form
     *
     * @param $mform
     * @return mixed
     */
    protected function internal_add_form_element(&$mform) {
        $mform->addElement('text', $this->fieldname, $this->fullname);
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
            'editor' => 'input'
        ];
    }

    /**
     * Get the matching formatter type and parameters to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function get_column_formatter() {
        return (object) [
            'formatter' => 'textarea'
        ];
    }

    /**
     * Get the matching validator type to be used in the table
     *
     * @return string|null return the type (and null if no filter)
     *
     */
    public function get_column_validator() {
        return (object) [
            'validator' => 'string'
        ];
    }

}