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

class file_manager  extends base  {
    protected $filemanageroptions =  null;
    public function __construct($fielddef) {
        parent::__construct($fielddef);
        if (is_array($fielddef)) {
            $fielddef = (object) $fielddef;
        }
        $this->filemanageroptions = empty($fielddef->filemanageroptions) ? [] : $fielddef->filemanageroptions;
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @return mixed
     */
    protected function internal_add_form_element(&$mform) {
        $mform->addElement('filemanager', $this->fieldname, $this->fullname, $this->filemanageroptions);
    }

    public function get_formatter_parameters() {
        return [
            'filemanageroptions' => $this->filemanageroptions
        ];
    }

    /**
     * Get the additional information related to the way we need to format this
     * information
     *
     * @return array|null associatvie array with related information on the way
     * to filter the data.
     *
     */
    public function get_filter_parameters() {
        return [
            'filemanageroptions' => $this->filemanageroptions
        ];
    }


}