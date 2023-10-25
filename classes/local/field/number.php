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

use core\persistent;
use MoodleQuickForm;
use renderer_base;

/**
 * Number field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class number extends persistent_field {
    /**
     * @var bool|mixed $isfloat
     */
    protected $isfloat = false;

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * @param bool $isfloat is a float
     */
    public function __construct($fielnameordef, bool $isfloat = false) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => $isfloat ? PARAM_FLOAT : PARAM_INT,
                'default' => 0,
        ];
        $this->isfloat = $isfloat;
        $this->init($fielnameordef, $standarddefaults);
    }

    /**
     * Get the matching filter type to be used for display
     *
     * @return object|null return the type (and null if no filter)
     *
     */
    public function get_column_filter(): ?object {
        return (object) [
                'filter' => $this->get_type(),
        ];
    }

    /**
     * Get the matching formatter type to be used for display
     *
     * @return object|null return the type (and null if no formatter)
     *
     */
    public function get_column_formatter(): ?object {
        $format = parent::get_column_formatter();
        $format->formatter = $this->get_type();
        return $format;
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @return object|null return the type (and null if no filter)
     *
     */
    public function get_column_editor(): ?object {
        return (object) [
                'editor' => $this->get_type(),
        ];
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @return object|null return the type (and null if no filter)
     *
     */
    public function get_column_validator(): ?object {
        return (object) [
                'validator' => $this->isfloat ? 'float' : 'integer',
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
        return $value;
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void {
        $mform->addElement($this->get_form_field_type(), $this->get_name(), $this->get_display_name());
        $this->internal_form_add_element($mform);
    }

    /**
     * Get form field type
     *
     * @return string
     */
    public function get_form_field_type():string {
        return $this->isfloat ? 'float' : 'text';
    }
}
