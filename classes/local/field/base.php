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
abstract class base  {

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
        $this->fullname = empty($fielddef->fullname) ? PARAM_RAW : $fielddef->fullname;
        $this->fieldname = empty($fielddef->fieldname) ? PARAM_RAW : $fielddef->fieldname;
    }
    /**
     * @param array $arguments
     * @return static
     */
    public static function get_instance_from_def($type = "text", $fielddef = null) {
        $classname = __NAMESPACE__.'\\'. $type;
        if (!class_exists($classname)) {
            $type = 'local_cltools\\local\\field\\text';
        }
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
     * Get the additional information related to the way we need to format this
     * information
     *
     * @return array|null associatvie array with related information on the way
     * to format the data.
     *
     */
    abstract public function get_formatter_parameters();

    /**
     * Get the additional information related to the way we need to filter this
     * information
     *
     * @return array|null associatvie array with related information on the way
     * to filter the data.
     *
     */
    abstract public function get_filter_parameters();


    /**
     * Add element onto the form
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function add_form_element(&$mform, $name, $fullname, $options = null) {
        $this->internal_add_form_element($mform, $this->fieldname, $this->fullname);
        $mform->setType($name, $this->get_raw_param_type());
        if ($this->required) {
            $mform->addRule($name, get_string('required'), 'required');
        }
        if ($this->default) {
            $mform->setDefault($name, $this->default);
        }
    }

    abstract protected function internal_add_form_element(&$mform, $name, $fullnamel);
}