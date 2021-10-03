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
use context_system;
use core\persistent;
use local_cltools\local\crud\entity_utils;
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

    public function __construct($fielnameordef) {
        $standarddefaults = [
            'required' => false,
            'rawtype' => PARAM_INT,
            'filemanageroptions' => [],
            'default' => 0 // No default if not it makes it required
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        if ($fielddef->filemanageroptions) {
            $this->filemanageroptions = $fielddef->filemanageroptions;
        }
    }

    /**
     * Form field type for this field, used in default implementation of form_add_element
     */
    const FORM_FIELD_TYPE = 'filemanager';
    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform,  ...$additionalargs) {
        $mform->addElement(static::FORM_FIELD_TYPE, $this->get_name(), $this->get_display_name(), $this->filemanageroptions);
        parent::internal_form_add_element($mform);
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     */
    public function internal_form_add_element($mform, $elementname = '') {
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
     * Filter persistent data submission
     *
     * @param $data
     * @return mixed
     */
    public function filter_data_for_persistent($itemdata) {
        unset($itemdata->{$this->fieldname});
        return $itemdata;
    }

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param \stdClass $itemdata
     * @param persistent $persistent
     * @return \stdClass
     */
    public function form_prepare_files($itemdata, persistent $persistent) {
        [$context, $component, $filearearoot, $itemid] = $this->get_file_info_context($persistent);

        $draftitemid = file_get_submitted_draft_itemid($this->fieldname);
        file_prepare_draft_area($draftitemid,
            $context->id,
            $component,
            $filearearoot . '_' . $this->get_name(),
            $itemid,
            $this->filemanageroptions);
        $itemdata->{$filemanagerformelt} = $draftitemid;
        return $itemdata;
    }

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param \stdClass $itemdata
     * @param persistent $persistent
     * @return \stdClass
     */
    public function form_save_files($itemdata, persistent $persistent) {
        [$context, $component, $filearearoot, $itemid] = $this->get_file_info_context($persistent);

        file_save_draft_area_files($itemdata->{$this->fieldname},
            $context->id,
            $component,
            $filearearoot . '_' . $this->get_name(),
            $itemid,
            $this->filemanageroptions);
        return $itemdata;
    }


    /**
     * Is in persistent table ?
     *
     */
    public function is_in_persistent_definition() {
        return false;
    }

    /**
     * Return a printable version of the current value
     *
     * @param int $value
     * @param mixed $additionalcontext
     * @return mixed
     */
    public function format_value($itemid, $additionalcontext = null) {
        $filesurl = [];
        if (!empty($additionalcontext['persistent'])) {
            $persistent = !empty($additionalcontext['persistent']) ? $additionalcontext['persistent'] : null;
            [$context, $component, $filearearoot, $itemid] = $this->get_file_info_context($persistent);
            $files = entity_utils::get_files($itemid, $filearearoot . '_' . $this->get_name(), $component, $context);
            foreach ($files as $index => $f) {
                if (!$f->is_directory() &&
                    file_mimetype_in_typegroup($f->get_mimetype(), ['web_image', 'document'])) {
                    $filesurl[] = \moodle_url::make_pluginfile_url(
                        $context->id,
                        $component,
                        $filearearoot . '_' . $this->get_name(),
                        $itemid,
                        $f->get_filepath(),
                        $f->get_filename()
                    );
                }
            }
        }
        return $filesurl;
    }

}
