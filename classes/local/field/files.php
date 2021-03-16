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

class files extends base  {

    protected $filemanageroptions  = [];

    public function __construct($fielddef)  {
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
     * @param $mform
     * @return mixed
     */
    public function internal_add_form_element(&$mform) {
        $options = $this->filemanageroptions;
        $mform->addElement('filemanager',  $this->fieldname, $this->fullname, null, $options);
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

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param $itemdata
     * @throws \coding_exception
     */
    public function prepare_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) =  $args;
        $draftitemid = file_get_submitted_draft_itemid($this->fieldname);
        file_prepare_draft_area($draftitemid,
            $context->id,
            $component,
            $filearea,
            $itemid,
            $this->filemanageroptions);
        $itemdata->{$filemanagerformelt} = $draftitemid;
    }

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param $itemdata
     * @throws \coding_exception
     */
    public function save_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) =  $args;
        file_save_draft_area_files($itemdata->{$this->fieldname},
            $context->id,
            $component,
            $filearea,
            $itemid,
            $this->filemanageroptions);
    }

}