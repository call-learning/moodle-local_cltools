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
abstract class base {

    protected $formatparameters = null;

    protected $required = false;

    protected $default = null;
    /**
     * @param array $arguments
     * @return static
     */
    public static function get_instance_from_type($type = "plain_text") {
        if (!class_exists(__NAMESPACE__.'\\'. $type)) {
            $type = 'plain_text';
        }
        return new $type();
    }
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
    abstract public function get_format_type();

    /**
     *
     * @param object $parameters
     *
     */
    public function set_formatter_parameters($parameters) {
        $this->formatparameters = $parameters;
    }

    /**
     * Get parameters given when constructing the
     *
     * @return object
     */
    public function get_formatter_parameters() {
        return $this->formatparameters;
    }


    /**
     *
     * @param object $parameters
     *
     */
    public function set_required($required) {
        $this->required = $required;
    }

    /**
     *
     * @param object $parameters
     *
     */
    public function set_default($default) {
        $this->default = $default;
    }

    /**
     * Get the type of parameter (see PARAM_XXX) for this type
     *
     * @return mixed
     */
    abstract public function get_param_type();

    /**
     * Add element onto the form
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function add_form_element(&$mform, $name, $fullname, $options = null) {
        $this->internal_add_form_element($mform, $name, $fullname, $options = null);
        $mform->setType($name, $this->get_param_type());
        if ($this->required) {
            $mform->addRule($name, get_string('required'), 'required');
        }
        if ($this->default) {
            $mform->setDefault($name, $this->default);
        }
    }

    abstract protected function internal_add_form_element(&$mform, $name, $fullname, $options = null);
}