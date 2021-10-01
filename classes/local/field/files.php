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
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();
/**
 * File field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files extends persistent_field {

    protected $filemanageroptions = [];

    public function __construct($fielddef) {
        parent::__construct($fielddef);
        if (is_array($fielddef)) {
            $fielddef = (object) $fielddef;
        }
        if (!empty($fielddef->filemanageroptions)) {
            $this->filemanageroptions = $fielddef->filemanageroptions;
        }
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform,  ...$additionalargs) {
        $mform->addElement('filemanager', $this->get_name(), $this->get_display_name(), $this->filemanageroptions);
        parent::internal_form_add_element($mform);
    }

    /**
     * Check if the field is visible or not
     *
     * @return boolean visibility
     *
     */
    public function is_visible() {
        return false;
    }
}
