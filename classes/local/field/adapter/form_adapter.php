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
 * Form adapter for field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\field\adapter;

use local_cltools\local\crud\enhanced_persistent;
use MoodleQuickForm;
use stdClass;

/**
 * Form adapter for field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface form_adapter {
    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     * @return void
     */
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void;

    /**
     * Filter persistent data submission
     *
     * @param stdClass $data
     * @return stdClass
     */
    public function filter_data_for_persistent(stdClass $data): stdClass;

    /**
     * Is this field part of the persistent definition
     *
     * @return bool
     */
    public function is_persistent(): bool;

    /**
     * Can we edit this field
     *
     * @return bool
     */
    public function can_edit(): bool;

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param stdClass $itemdata
     * @param enhanced_persistent $persistent
     * @return stdClass
     */
    public function form_prepare_files(stdClass $itemdata, enhanced_persistent $persistent): stdClass;

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param stdClass $itemdata
     * @param enhanced_persistent $persistent
     * @return stdClass
     */
    public function form_save_files(stdClass $itemdata, enhanced_persistent $persistent): stdClass;
}
