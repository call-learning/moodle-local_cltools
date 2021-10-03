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

defined('MOODLE_INTERNAL') || die();
/**
 * Base field test
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_test extends advanced_testcase {
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
     * Get the matching filter type and parameters to be used for display
     *
     *
     * @link  http://tabulator.info/docs/4.9/filter
     * @return object|null return the parameters (or null if no matching filter)
     *
     */
    public function test_get_column_filter() {

    }

    /**
     * Get the matching editor type and parameters to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function test_get_column_editor() {
    }
    /**
     * Get the matching formatter type and parameters to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function test_get_column_formatter() {
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link http://tabulator.info/docs/4.9/validate
     * @return object|null return the parameters (or null if no matching validator)
     *
     */
    public function test_get_column_validator() {
    }

    /**
     * Check if the field is visible or not
     *
     * @return boolean visibility
     *
     */
    public function test_is_visible() {
        $field = new boolean(['fieldname' => 'fieldname', 'visible' => false]);
        $this->assertFalse($field->is_visible());
        $field = new boolean(['fieldname' => 'fieldname', 'visible' => true]);
        $this->assertTrue($field->is_visible());
    }
    /**
     * Call
     */

    /**
     * Callback for this field, so data can be converted before sending it to a persistent
     *
     * @param $data
     */
    public function filter_data_for_persistent(&$itemdata, ...$args) {

    }

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param $data
     */
    public function test_prepare_files() {
    }

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param $data
     */
    public function test_save_files() {
    }

    /**
     * Get addional joins
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return array
     */
    public function test_get_additional_sql() {
    }

    /**
     * Get addional additional invisible sort field
     *
     * Not necessary most of the time
     *
     * @param $entityalias
     * @return string
     */
    public function test_get_additional_util_field() {
    }
}
