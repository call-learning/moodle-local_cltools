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
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die;

/**
 * Form adapter for field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait form_adapter_default {
    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function form_add_element(MoodleQuickForm $mform,  ...$additionalargs) {
        $mform->addElement(static::FORM_FIELD_TYPE, $this->get_name(), $this->get_display_name());
        $this->internal_form_add_element($form);
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function internal_form_add_element(&$mform, $elementname = '') {
        if (empty($elementname)) {
            $elementname = $this->get_name();
        }
        $mform->setType($elementname, $this->get_raw_param_type());
        if ($this->is_required()) {
            $mform->addRule($elementname, get_string('required'), 'required');
        }
        if ($this->default) {
            $mform->setDefault($elementname, $this->default);
        }
    }

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param $itemdata
     * @throws coding_exception
     */
    public function prepare_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) = $args;
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
     * @throws coding_exception
     */
    public function save_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) = $args;
        file_save_draft_area_files($itemdata->{$this->fieldname},
            $context->id,
            $component,
            $filearea,
            $itemid,
            $this->filemanageroptions);
    }


}