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
use html_writer;
use local_cltools\local\crud\enhanced_persistent;
use local_cltools\local\crud\entity_utils;
use moodle_url;
use MoodleQuickForm;
use renderer_base;
use stdClass;
use stored_file;

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

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
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
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void {
        $persistent = $additionalargs[0] ?? null;
        $mform->addElement($this->get_form_field_type(), $this->get_name(), $this->get_display_name(),
                $this->get_filemanager_options($persistent));
        parent::internal_form_add_element($mform);
    }

    /**
     * Get form field type
     *
     * @return string
     */
    public function get_form_field_type(): string {
        return "filemanager";
    }

    /**
     * Get file options
     *
     * @param enhanced_persistent|null $persistent
     * @return array
     */
    protected function get_filemanager_options(?enhanced_persistent $persistent): array {
        $filemanageroptions = $this->filemanageroptions;
        if ($persistent) {
            $filemanageroptions['context'] = $persistent->get_context();
        }
        return $filemanageroptions;
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param string $elementname
     * @return void
     */
    public function internal_form_add_element(MoodleQuickForm $mform, string $elementname = ''): void {
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
     * @param stdClass $itemdata
     * @return stdClass
     */
    public function filter_data_for_persistent(stdClass $itemdata): stdClass {
        unset($itemdata->{$this->fieldname});
        return $itemdata;
    }

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param stdClass $itemdata
     * @param enhanced_persistent $persistent
     * @return stdClass
     */
    public function form_prepare_files(stdClass $itemdata, enhanced_persistent $persistent): stdClass {
        $fieldname = $this->get_name();
        if (empty($itemdata->$fieldname)) {
            return $itemdata;
        }
        [$context, $component, $filearea, $itemid] = $this->get_file_info_context($fieldname, $persistent);
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
     * @param enhanced_persistent $persistent
     * @return stdClass
     */
    public function form_save_files(stdClass $itemdata, enhanced_persistent $persistent): stdClass {
        $fieldname = $this->get_name();
        if (empty($itemdata->$fieldname)) {
            return $itemdata;
        }
        [$context, $component, $filearea, $itemid] = $this->get_file_info_context($fieldname, $persistent);

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
     * @return bool
     */
    public function is_persistent():bool {
        return false;
    }

    /**
     * Return a printable version of the current value
     *
     * @param mixed $value
     * @param persistent|null $persistent
     * @param renderer_base|null $renderer
     * @return string
     */
    public function format_value($value, ?persistent $persistent = null, ?renderer_base $renderer = null): string {
        $filesurl = [];
        if (!empty($persistent)) {
            $fieldname = $this->get_name();
            [$context, $component, $filearea, $value] = $this->get_file_info_context($fieldname, $persistent);
            $files = entity_utils::get_files($value, $filearea, $component, $context);
            foreach ($files as $f) {
                /* @var  stored_file $f information for the current file */
                if (!$f->is_directory() &&
                        file_mimetype_in_typegroup($f->get_mimetype(), ['web_image', 'document'])) {
                    if (!empty($value) && $value != $f->get_itemid()) {
                        continue; // Only display the file specified by the value.
                    }
                    $filesurl[] = moodle_url::make_pluginfile_url(
                            $f->get_id(),
                            $f->get_component(),
                            $f->get_filearea(),
                            $f->get_itemid(),
                            $f->get_filepath(),
                            $f->get_filename()
                    );
                }
            }
        }
        return html_writer::alist($filesurl);
    }
}
