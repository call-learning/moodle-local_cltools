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

use core\persistent;
use local_cltools\local\crud\enhanced_persistent;
use local_cltools\local\crud\entity_utils;
use moodle_url;
use MoodleQuickForm;
use stdClass;

/**
 * File field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class files extends persistent_field {
    /**
     * File manager options
     *
     * @var array
     */
    protected $filemanageroptions = [];

    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_INT,
                'filemanageroptions' => [],
                'default' => 0 // No default if not it makes it required.
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        if ($fielddef->filemanageroptions) {
            $this->filemanageroptions = $fielddef->filemanageroptions;
        }
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm $mform, ...$additionalargs) {
        $persistent = $additionalargs[0] ?? null;
        $mform->addElement($this->get_form_field_type(), $this->get_name(), $this->get_display_name(),
                $this->get_filemanager_options($persistent));
        parent::internal_form_add_element($mform);
    }

    /**
     * Get file options
     *
     * @param enhanced_persistent|null $persistent
     * @return array
     */
    protected function get_filemanager_options(?enhanced_persistent $persistent) {
        $filemanageroptions = $this->filemanageroptions;
        if ($persistent) {
            $filemanageroptions['context'] = $persistent->get_context();
        }
        return $filemanageroptions;
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
     * @param stdClass $itemdata
     * @param persistent $persistent
     * @return stdClass
     */
    public function form_prepare_files($itemdata, persistent $persistent) {
        $fieldname = $this->get_name();
        [$context, $component, $filearea, $itemid] = $this->get_file_info_context($persistent, $fieldname);
        $draftitemid = file_get_submitted_draft_itemid($fieldname);
        file_prepare_draft_area($draftitemid,
                $context->id,
                $component,
                $filearea,
                $itemid,
                $this->get_filemanager_options($persistent));
        $itemdata->$fieldname = $draftitemid;
        return $itemdata;
    }

    /**
     * Callback for this field, so data can be saved after form submission
     *
     * @param stdClass $itemdata
     * @param persistent $persistent
     * @return stdClass
     */
    public function form_save_files($itemdata, persistent $persistent) {
        $fieldname = $this->get_name();
        [$context, $component, $filearea, $itemid] = $this->get_file_info_context($persistent, $fieldname);

        file_save_draft_area_files($itemdata->$fieldname,
                $context->id,
                $component,
                $filearea,
                $itemid,
                $this->get_filemanager_options($persistent));
        return $itemdata;
    }

    /**
     * Is in persistent table ?
     *
     */
    public function is_persistent() {
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
            $fieldname = $this->get_name();
            [$context, $component, $filearea, $itemid] = $this->get_file_info_context($persistent, $fieldname);
            $files = entity_utils::get_files($itemid, $filearea, $component, $context);
            foreach ($files as $index => $f) {
                if (!$f->is_directory() &&
                        file_mimetype_in_typegroup($f->get_mimetype(), ['web_image', 'document'])) {
                    $filesurl[] = moodle_url::make_pluginfile_url(
                            $context->id,
                            $component,
                            $filearea,
                            $itemid,
                            $f->get_filepath(),
                            $f->get_filename()
                    );
                }
            }
        }
        return $filesurl;
    }

    /**
     * Get form field type
     * @return string
     */
    public function get_form_field_type() {
        return "filemanager";
    }
}
