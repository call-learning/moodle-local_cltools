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
use local_cltools\local\crud\enhanced_persistent;
use MoodleQuickForm;
use renderer_base;
use stdClass;

/**
 * Text editor field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor extends persistent_field {
    /**
     * Editor options
     *
     * @var array
     */
    protected $editoroptions = [];

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_RAW,
                'default' => '',
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        if (!empty($fielddef->editoroptions)) {
            $this->editoroptions = $fielddef->editoroptions;
        }
    }

    /**
     * Check if the field is visible or not
     *
     * @return bool visibility
     *
     */
    public function is_visible(): bool {
        return false;
    }

    /**
     * Callback for this field, so data can be converted before form submission
     *
     * @param stdClass $itemdata
     * @param enhanced_persistent $persistent $persistent
     * @return stdClass
     */
    public function form_prepare_files($itemdata, enhanced_persistent $persistent): stdClass {
        $fieldname = $this->get_name();
        list($context, $component, $filearea, $itemid) = $this->get_file_info_context($fieldname, $persistent);
        // Tweak the content, so we get the submitted text if ever we failed to submit the full form and come back to the
        // intial form (case persistent is not yet created).
        if (!empty($itemdata->{$fieldname . '_editor'})) {
            $itemdata->$fieldname = $itemdata->$fieldname ?? $itemdata->{$fieldname . '_editor'}['text'];
            $itemdata->{$fieldname . 'format'} =
                    $itemdata->{$fieldname . 'format'} ?? $itemdata->{$fieldname . '_editor'}['format'];
        }
        return file_prepare_standard_editor($itemdata,
                $fieldname,
                $this->get_editor_options($persistent),
                $context,
                $component,
                $filearea,
                $itemid
        );
    }

    /**
     * Get editor options (to manage files)
     *
     * @param enhanced_persistent|null $persistent
     * @return array
     */
    protected function get_editor_options(?enhanced_persistent $persistent) {
        global $CFG;
        $defaultoptions = [
                'context' => $persistent ? $persistent->get_context() : context_system::instance(),
                'enable_filemanagement' => true,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'changeformat' => true,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => true,
        ];
        return array_merge($defaultoptions, $this->editoroptions);
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
        list($context, $component, $filearea, $itemid) = $this->get_file_info_context($fieldname, $persistent);
        $data = file_postupdate_standard_editor($itemdata, $fieldname,
                $this->get_editor_options($persistent),
                $context,
                $component,
                $filearea,
                $itemid);
        $fieldnameformat = $fieldname . 'format';
        $itemdata->$fieldname = $data->$fieldname;
        $itemdata->$fieldnameformat = $data->$fieldnameformat;
        return $itemdata;
    }

    /**
     * Filter persistent data submission
     *
     * @param stdClass $itemdata
     * @return stdClass
     */
    public function filter_data_for_persistent(stdClass $itemdata): stdClass {
        if (!empty($itemdata->{$this->get_name() . '_editor'})) {
            if (empty($itemdata->{$this->get_name()})) {
                $itemdata->{$this->get_name()} = $itemdata->{$this->get_name() . '_editor'}['text'] ?? '';
            }
            unset($itemdata->{$this->get_name() . '_editor'});
        }
        if (isset($itemdata->{$this->get_name() . 'trust'})) {
            unset($itemdata->{$this->get_name() . 'trust'});
        }

        return $itemdata;
    }

    /**
     * Return a printable version of the value provided in input
     *
     * @param persistent|null $persistent
     * @param renderer_base|null $renderer
     * @return string
     */
    public function format_value(?persistent $persistent = null, ?renderer_base $renderer = null): string {
        $value = parent::format_value($persistent, $renderer);
        if (!empty($persistent)) {
            $fieldname = $this->get_name();
            [$context, $component, $filearea, $itemid] = $this->get_file_info_context($fieldname, $persistent);
            return file_rewrite_pluginfile_urls($value, 'pluginfile.php',
                    $context->id, $component, $filearea, $itemid);
        }
        return $value;
    }

    /**
     * Get persistent properties
     *
     * @return array[]
     */
    public function get_persistent_properties(): array {
        $property = [];
        $property['type'] = $this->get_raw_param_type();
        $property['null'] = $this->is_required();

        return [
                $this->get_name() => $property,
                "{$this->get_name()}format" => [
                        'type' => PARAM_INT,
                        'default' => FORMAT_HTML,
                        'choices' => [FORMAT_PLAIN, FORMAT_HTML, FORMAT_MOODLE, FORMAT_MARKDOWN],
                ],
        ];
    }

    /**
     * Get additional fields
     *
     * @param string $entityalias
     *
     * @return array of SQL fields (['xxx AS yyy', '...])
     */
    public function get_additional_fields(string $entityalias = 'e'): array {
        return ["{$entityalias}.{$this->get_name()}format"];
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate_value($value): bool {
        return is_string($value);
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void {
        $elementname = $this->get_name() . '_editor';
        $persistent = $additionalargs[0] ?? null;
        $editoroptions = $this->get_editor_options($persistent);

        $mform->addElement($this->get_form_field_type(), $elementname, $this->get_display_name(), null, $editoroptions);
        parent::internal_form_add_element($mform, $elementname);
        $mform->setType($this->get_name(), PARAM_RAW);
    }

    /**
     * Get form field type
     *
     * @return string
     */
    public function get_form_field_type(): string {
        return "editor";
    }
}
