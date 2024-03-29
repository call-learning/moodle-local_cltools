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
 * Enhanced entity implementation
 *
 * @package   local_cltools
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;

trait enhanced_persistent_impl {
    /**
     * Return defined properties from
     *
     * @return array
     */
    protected static function define_properties(): array {
        $properties = [];
        foreach (static::define_fields() as $field) {
            if ($field->is_persistent()) {
                $properties = array_merge($properties, $field->get_persistent_properties());
            }
        }
        return $properties;
    }
    // TODO: define fields from table XML definition.

    /**
     * Return defined properties from
     *
     * @return array
     */
    protected static function get_all_properties(): array {
        $properties = [];
        foreach (static::define_fields() as $field) {
            $properties = array_merge($properties, $field->get_persistent_properties());
        }
        return $properties;
    }

    /**
     * Get persistent context
     *
     * @return mixed
     */
    public function get_context() {
        global $PAGE;
        return $PAGE->context;
    }
}
