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

/**
 * Blank html field.
 *
 * This is not a persistent field but a field that is calculated through other means.
 * Used mostly in table to add a calculated field.
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blank_field extends persistent_field {
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
                'default' => '',
                'editable' => false
        ];
        $this->init($fielnameordef, $standarddefaults);
        $this->sortable = false; // Not sortable for now.
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function get_column_formatter(): ?object {
        $format = parent::get_column_formatter();
        $format->formatter = 'html';
        return $format;
    }

    /**
     * Can we edit this field
     *
     * @return bool
     */
    public function can_edit(): bool {
        return false;
    }

    /**
     * Is this field part of the persistent definition
     *
     * @return bool
     */
    public function is_persistent(): bool {
        return false;
    }

    /**
     * Can we sort the column ?
     *
     * @return bool
     */
    public function can_sort(): bool {
        return false;
    }
}
