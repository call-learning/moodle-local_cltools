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


namespace local_cltools\local\formatter;
defined('MOODLE_INTERNAL') || die();

class entity_selector  extends base  {

    /**
     * Return a printable version of the current value
     * @param $value
     * @return mixed
     */
    public function get_printable($value, ...$additionalargs) {
        return $value;
    }

    /**
     * Get an identifier for this type of format
     *
     * @return mixed
     */
    public function get_format_type() {
        return 'choicelist';
    }

    /**
     * Get an identifier for this type of format
     *
     * @return mixed
     */
    public function get_param_type() {
        return PARAM_INT;
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     * @throws \dml_exception
     */
    public function internal_add_form_element(&$mform, $name, $fullname, $options = null) {
        $params = $this->get_formatter_parameters();
        $mform->addElement('searchableselector', $name, $fullname, $params->choices);
    }
}