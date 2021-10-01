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
use coding_exception;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();
/**
 * Text editor field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor extends persistent_field {
    /**
     * Construct the field from its definition
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
            'required' => false,
            'rawtype' => PARAM_TEXT,
            'default' => ''
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        if (!empty($fielddef->editoroptions)) {
            $this->editoroptions = $fielddef->editoroptions;
        }
    }

    protected $editoroptions = [];

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform,  ...$additionalargs) {
        $elementname = $this->get_name() . '_editor';
        $mform->addElement('editor', $elementname, $this->get_display_name());
        parent::internal_form_add_element($mform, $elementname);
        $mform->setType($this->get_name(), PARAM_RAW);
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
     * @throws coding_exception
     */
    public function prepare_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) = $args;
        file_prepare_standard_editor($itemdata,
            $this->fieldname,
            $this->editoroptions,
            $context,
            $component,
            $filearea,
            $itemid
        );
    }

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param $itemdata
     * @throws coding_exception
     */
    public function save_files(&$itemdata, ...$args) {
        list($context, $component, $filearea, $itemid) = $args;
        $data = file_postupdate_standard_editor($itemdata, $this->fieldname,
            $this->editoroptions,
            $context,
            $component,
            $filearea,
            $itemid);
        $itemdata->{$this->fieldname} = $data->{$this->fieldname};
        $itemdata->{$this->fieldname . 'format'} = $data->{$this->fieldname . 'format'};
    }

    /**
     * Callback for this field, so data can be converted before sending it to a persistent
     *
     * @param $data
     */
    public function filter_data_for_persistent(&$itemdata, ...$args) {
        if (!empty($itemdata->{$this->fieldname . '_editor'})) {
            unset($itemdata->{$this->fieldname . '_editor'});
        }
        if (isset($itemdata->{$this->fieldname . 'trust'})) {
            unset($itemdata->{$this->fieldname . 'trust'});
        }
    }

}
