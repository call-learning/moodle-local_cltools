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

use advanced_testcase;

/**
 * Base field test
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persistent_field_test extends advanced_testcase {
    /**
     * Get the name of this field
     */
    public function test_get_name() {
        $field = new boolean(['fieldname' => 'fieldname', 'fullname' => 'Full Name']);
        $this->assertEquals('fieldname', $field->get_name());

        $field = new editor(['fieldname' => 'fieldname', 'fullname' => 'Full Name']);
        $this->assertEquals('fieldname', $field->get_name());
    }

    /**
     * Check if the field is visible or not
     *
     */
    public function test_is_visible() {
        $field = new boolean(['fieldname' => 'fieldname', 'visible' => false]);
        $this->assertFalse($field->is_visible());
        $field = new boolean(['fieldname' => 'fieldname', 'visible' => true]);
        $this->assertTrue($field->is_visible());
    }

}
