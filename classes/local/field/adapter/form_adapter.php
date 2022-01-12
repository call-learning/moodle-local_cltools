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
namespace local_cltools\local\field\adapter;

use core\persistent;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die;

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
     * @return mixed
     */
    public function form_add_element(MoodleQuickForm $mform, ...$additionalargs);

    /**
     * Filter persistent data submission
     *
     * @param $data
     * @return mixed
     */
    public function filter_data_for_persistent($data);

    /**
     * Is this field part of the persistent definition
     *
     * @return mixed
     */
    public function is_persistent();

    /**
     * Can we edit this field
     *
     * @return bool
     */
    public function can_edit();

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param stdClass $itemdata
     * @param persistent $persistent
     * @return stdClass
     */
    public function form_prepare_files($itemdata, persistent $persistent);

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param stdClass $itemdata
     * @param persistent $persistent
     * @return stdClass
     */
    public function form_save_files($itemdata, persistent $persistent);
}
