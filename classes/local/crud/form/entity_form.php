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
 * Rotation entity edit or add form
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\form;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\form\persistent;
use dml_exception;
use HTML_QuickForm_element;
use local_cltools\local\crud\entity_utils;
use moodleform;
use MoodleQuickForm;
use ReflectionException;
use stdClass;

// Custom form element types.
global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/cltools/form/register_form_elements.php');

/**
 * Entity form abstract class.
 *
 * This provides some shortcuts to validate objects based on the persistent model.
 *
 * Note that all mandatory fields (non-optional) of your model should be included in the
 * form definition. Mandatory fields which are not editable by the user should be
 * as hidden and constant.
 *
 *    $mform->addElement('hidden', 'userid');
 *    $mform->setType('userid', PARAM_INT);
 *    $mform->setConstant('userid', $this->_customdata['userid']);
 *
 * You may exclude some fields from the validation should your form include other
 * properties such as files. To do so use the $foreignfields property.
 *
 * @package    local_cltools
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity_form extends persistent {

    /**
     * Editor suffix
     */
    const EDITOR_SUFFIX = '_editor';
    /** @var array Fields to remove when getting the final data. */
    protected static $fieldstoremove = ['submitbutton'];
    /**
     * @var array|mixed $fields form fields
     */
    protected $fields = [];

    /**
     * Persistent form constructor.
     *
     * @param mixed $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param mixed $attributes
     * @param bool $editable
     * @param array $ajaxformdata
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
            $editable = true, $ajaxformdata = null) {
        $this->fields = entity_utils::get_defined_fields(static::$persistentclass);
        // TODO: at some point we will need to get rid of persistentclass as static.
        // The only exception right not if the provided persistent is null...

        if (!$customdata) {
            $customdata = ['persistent' => null];
        }
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * The form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $this->pre_field_definitions($mform);
        $hasidfield = false;
        foreach ($this->fields as $name => $field) {
            $field->form_add_element($mform, $this->get_persistent());
            if ($field->get_name() == 'id') {
                $hasidfield = true;
            }
        }
        if (!$hasidfield && $this->get_persistent() && $this->get_persistent()->get('id') > 0) {
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);
            $mform->setConstant('id', $this->get_persistent()->get('id'));
        }
        $this->post_field_definitions($mform);
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Hook before field definition
     *
     * @param MoodleQuickForm $mform
     * Additional definitions for the form (before we add the fields)
     */
    protected function pre_field_definitions(&$mform) {
    }

    /**
     * Hook after field definition
     *
     * @param MoodleQuickForm $mform
     * Additional definitions for the form (after we add the fields)
     */
    protected function post_field_definitions(&$mform) {
    }

    /**
     * Set the default values for file
     *
     * Either from the optional parameter or the itemid
     *
     * @throws ReflectionException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function prepare_for_files() {
        $item = $this->get_persistent();
        $itemdata = $item->to_record();
        $currentdata = $this->_form->exportValues();
        if ($currentdata) {
            $itemdata = (object) array_merge((array) $currentdata, (array) $itemdata);
        }
        foreach ($this->fields as $field) {
            $itemdata = $field->form_prepare_files($itemdata, $this->get_persistent());
        }
        $this->set_data($itemdata);
    }

    /**
     * Save current data in persistent
     *
     * @return \core\persistent
     */
    public function save_data(): \core\persistent {

        $data = moodleform::get_data();
        $persistentdata = $this->filter_data_for_persistent($data);
        $persistent = $this->get_persistent();
        $persistent->from_record((object) $persistentdata);
        $persistent->save();

        // Then save the files as the id is now updated.
        $data = $this->save_submitted_files($data);
        // Then update.
        $persistentdata = $this->filter_data_for_persistent($data);
        $persistent->from_record((object) $persistentdata);
        $persistent->update();

        return $persistent;
    }

    /**
     * Get form data.
     *
     * Conveniently removes non-desired properties and add the ID property.
     *
     * @return object|null
     */
    public function get_data(): ?object {
        $data = moodleform::get_data();
        if (is_object($data)) {
            foreach (static::$fieldstoremove as $field) {
                unset($data->{$field});
            }
            $data = static::convert_fields($data);

            // Ensure that the ID is set.
            $data->id = $this->get_persistent()->get('id');
        }
        return $data;
    }

    /**
     * Filter out the foreign fields of the persistent.
     *
     * This can be overridden to filter out more complex fields.
     *
     * @param stdClass $data The data to filter the fields out of.
     * @return object
     * @throws coding_exception
     */
    protected function filter_data_for_persistent($data): object {
        $filtereddata = parent::filter_data_for_persistent($data);
        foreach ($this->fields as $field) {
            $filtereddata = $field->filter_data_for_persistent($filtereddata);
        }
        if (!empty($filtereddata->submitbutton)) {
            unset($filtereddata->submitbutton);
        }
        return $filtereddata;
    }

    /**
     * Save submited files
     *
     * @param object $data
     * @return object
     */
    protected function save_submitted_files(object $data): object {
        foreach ($this->fields as $field) {
            $data = $field->form_save_files($data, $this->get_persistent());
        }
        return $data;
    }

    /**
     * Get options for filemanager
     *
     * @param array $fieldinfo
     * @return array
     */
    protected function filemanager_get_default_options(array &$fieldinfo): array {
        $formoptions = ['context' => $this->get_persistent()->get_context()];
        return $formoptions;
    }

    /**
     * Get options for editor
     *
     * @param array $fieldinfo
     * @return array
     */
    protected function editor_get_option(array $fieldinfo): array {
        global $CFG;
        $formoptions =
                [
                        'maxfiles' => EDITOR_UNLIMITED_FILES,
                        'maxbytes' => $CFG->maxbytes,
                        'noclean' => true,
                        'context' => $this->get_persistent()->get_context(),
                        'enable_filemanagement' => true,
                        'changeformat' => true,
                ];
        $formoptions =
                empty($forminfo['editoroptions']) ? $formoptions : array_merge($formoptions, $fieldinfo['editoroptions']);
        return $formoptions;
    }

    /**
     * Get all fields related to file
     *
     * @return array
     */
    protected function get_file_fields_info(): array {
        static $fields = [];
        if (!empty($fields)) {
            return $fields;
        }
        $mform = $this->_form;
        $fields = [];
        foreach ($mform->_elements as $e) {
            $elementtype = $e->getType();
            $elementname = $this->get_real_element_name($e);
            if ($elementname) {
                if (in_array($elementtype, ['filemanager', 'file', 'editor'])) {
                    $fields[$elementname] = $this->fields[$elementname];
                }
            }
        }
        return $fields;
    }

    /**
     * Tweak for editor element names as they are created with _editor suffix
     *
     * @param HTML_QuickForm_element $e
     * @return null|string
     */
    private function get_real_element_name(HTML_QuickForm_element $e): ?string {
        $elementname = $e->getName();
        if ($e->getType() == 'editor') {
            return static::remove_editor_suffix($elementname);
        } else {
            return $elementname;
        }
    }

    /**
     * Tweak for editor element names as they are created with _editor suffix
     *
     * @param string $fieldname
     * @return string
     */
    private static function remove_editor_suffix(string $fieldname): string {
        return (substr($fieldname, -strlen(self::EDITOR_SUFFIX)) == self::EDITOR_SUFFIX) ?
                substr($fieldname, 0, strlen($fieldname) - strlen(self::EDITOR_SUFFIX)) : $fieldname;
    }
}
