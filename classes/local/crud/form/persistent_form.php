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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\form;

use local_cltools\local\crud\persistent_utils;
use stdClass;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Persistent form abstract class.
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
 * @package    core
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_form extends \core\form\persistent {
    /** @var array Fields to remove when getting the final data. */
    protected static $fieldstoremove = array('submitbutton');

    protected $formdefinition = null;

    /**
     * persistent_form constructor.
     *
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     * @throws \coding_exception
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null,
        $editable = true, $ajaxformdata = null) {
        $this->formdefinition = $this->get_mix_persistent_form_properties();
        if (!$customdata) {
            $customdata = ['persistent' => null];
        }
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Usual properties definition for a persistent form
     *
     * @return array|array[]
     */
    protected static function define_properties() {
        return array();
    }

    /**
     * Additional definitions
     *
     * @return array|array[]
     */
    protected function additional_definitions(&$mform) {
        return array();
    }

    /**
     * Filter out the foreign fields of the persistent.
     *
     * This can be overridden to filter out more complex fields.
     *
     * @param stdClass $data The data to filter the fields out of.
     * @return object.
     * @throws \coding_exception
     */
    protected function filter_data_for_persistent($data) {
        $filtereddata = parent::filter_data_for_persistent($data);
        $allfilefields = $this->get_file_fields();
        foreach ($this->_form->_elements as $e) {
            $ename = $e->getName();
            if ($e->getType() == 'editor' && key_exists($ename, $allfilefields)) {
                unset($filtereddata->$ename);
            }
        }
        return $filtereddata;
    }

    /**
     * Convert some fields.
     *
     * Here we take care of the specific case of the editor
     *
     * @param stdClass $data The whole data set.
     * @return stdClass The amended data set.
     */
    protected static function convert_fields(\stdClass $data) {
        $properties = (static::$persistentclass)::get_formatted_properties();
        $expandeddata = clone $data;
        foreach ($data as $field => $value) {
            // Replace formatted properties.
            $fieldname = static::remove_editor_suffix($field);
            if (isset($properties[$fieldname])) {
                $formatfield = $properties[$fieldname];
                $expandeddata->$formatfield = $data->{$field}['format'];
                $expandeddata->$fieldname = $data->{$field}['text'];
                unset($expandeddata->$field);
            }
        }
        return $expandeddata;
    }

    /**
     * Get dynamic form properties
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    protected function get_mix_persistent_form_properties() {
        $properties = (static::$persistentclass)::properties_definition();
        $formproperties = $this->define_properties();
        $allproperties = array_merge(array_keys($properties), array_keys($formproperties));
        foreach ($allproperties as $name) {
            $forminfo = !empty($formproperties[$name])? (object)$formproperties[$name]: null;
            if (empty($forminfo)) {
                $forminfo = new \stdClass();
                $forminfo->type = 'hidden';
                $forminfo->rawtype = $prop['type'];
            }
            if (empty($properties[$name])) {
                switch ($forminfo->type) {
                    case 'file_manager':
                        $prop['type'] = 'filemanager';
                        $prop['default'] = '';
                        break;
                }
            } else {
                $prop = $properties[$name];
            }

            // Uniformise the name.
            if (empty($forminfo->fullname)) {
                $forminfo->fullname = persistent_utils::get_string_for_entity(static::$persistentclass, $name);
            }
            $possibledefault = '';
            if (persistent_utils::is_reserved_property($name)) {
                switch ($name) {
                    case 'id':
                        $possibledefault = 0;
                        break;
                    case 'timecreated':
                    case 'timemodified':
                        $possibledefault = time();
                        break;
                    case 'usermodified':
                        global $USER;
                        $possibledefault = $USER->id;
                        break;
                }
                $forminfo->type = 'hidden';
                $forminfo->rawtype = $prop['type'];
            }
            // Set default value.
            $forminfo->default = !empty($prop['default']) ? $prop['default'] : $possibledefault;

            // Now the type.

            if (empty($forminfo->type)) {
                switch ($prop['type']) {
                    // TODO: Deal with more form types and further attributes.
                    // Maybe we can have a closure for this as it happens for default value.
                    case PARAM_TEXT:
                    case PARAM_ALPHANUMEXT:
                    case PARAM_ALPHANUM:
                    case PARAM_ALPHA:
                        $forminfo->type = 'text';
                        $forminfo->rawtype = $prop['type'];
                        break;
                    case PARAM_INT:
                        if (!empty($prop['choices'])) {
                            $forminfo->choices = $prop['choices'];
                            $forminfo->type = 'select_choice';
                        } else {
                            $forminfo->type = 'int';
                        }
                        $forminfo->rawtype = PARAM_INT;
                        break;
                    case PARAM_BOOL:
                        $forminfo->type = 'bool';
                        $forminfo->rawtype = PARAM_BOOL;
                        break;
                    default:
                        $forminfo->type = 'text';
                        $forminfo->rawtype = PARAM_RAW;
                }
            } else {
                switch ($forminfo->type) {
                    case 'entities_sortable_list':
                    case 'editor':
                        $forminfo->rawtype = PARAM_RAW;
                        break;
                    case 'entity_selector':
                    case 'select_choice':
                    case 'int':
                        $forminfo->rawtype = PARAM_INT;
                        break;
                    case 'text':
                        $forminfo->rawtype = PARAM_TEXT;
                        break;
                    case 'bool':
                        $forminfo->rawtype = PARAM_BOOL;
                        break;
                    default:
                        $forminfo->rawtype = PARAM_RAW;
                }
            }
            $forminfo->required = (!empty($prop['null']) && $prop['null'] != NULL_ALLOWED) ? true : false;
            $formproperties[$name] = $forminfo; // Update it.
        }
        return $formproperties;

    }

    /**
     * The form definition.
     */
    public function definition() {
        $mform = $this->_form;
        foreach ($this->formdefinition as $name => $prop) {
            switch ($prop->type) {
                case 'file_manager':
                    global $DB;
                    $formoptions = $this->file_get_option($prop);
                    $mform->addElement('filemanager', $name, $prop->fullname, $formoptions);
                    $mform->setType($name, PARAM_INT);
                    break;
                case 'editor':
                    $formoptions = $this->editor_get_option($prop);
                    $mform->addElement('editor', $name . '_editor', $prop->fullname, null, $formoptions);
                    $mform->setType($name, PARAM_RAW);
                    break;
                case 'entity_selector':
                    global $DB;
                    $info = $prop->selector_info;
                    if (!$info) {
                        debugging('Issue with persistent form defintion for' . $prop->fullname);
                    } else {
                        $entitytype = $info->entity_type;
                        $allrecords = $DB->get_records_menu($entitytype::TABLE, null, $fields = 'id,' . $info->display_field);
                        $mform->addElement('searchableselector', $name, $prop->fullname, $allrecords);
                    }
                    break;
                case 'select_choice':
                    $choices = $prop->choices;
                    $mform->addElement('select', $name, $prop->fullname, $choices);
                    break;
                case 'text':
                case 'int':
                    $mform->addElement('text', $name, $prop->fullname);
                    break;
                case 'bool':
                    $mform->addElement('advcheckbox', $name, $prop->fullname);
                    break;
                case 'hidden':
                    $mform->addElement('hidden', $name, $prop->default);
                    break;
                default:
                    $mform->addElement($prop->type, $name, $prop->fullname);
            }
            $mform->setType($name, $prop->rawtype);
            // TODO: Deal with default value.
            if ($prop->required) {
                $mform->addRule($name, get_string('required'), 'required');
            }
            if ($prop->default) {
                $mform->setDefault($name, $prop->default);
            }
        }
        $this->additional_definitions($mform);
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Get options for editor
     *
     * @param $forminfo
     * @return array
     * @throws \dml_exception
     */
    protected function editor_get_option($forminfo) {
        global $CFG;
        $formoptions =
            [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => false,
                'noclean' => true,
                'context' => \context_system::instance()
            ];
        $formoptions =
            empty($forminfo->editor_options) ? $formoptions : array_merge($formoptions, $forminfo->editor_options);
        return $formoptions;
    }

    /**
     * Get options for filemanager
     *
     * @param $forminfo
     * @return array
     * @throws \dml_exception
     */
    protected function file_get_option($forminfo) {
        $formoptions = ['context' => \context_system::instance()];
        $formoptions =
            empty($forminfo->file_options) ? $formoptions : array_merge($formoptions, $forminfo->file_options);
        return $formoptions;
    }

    /**
     * Get all fields related to file
     *
     * @return array
     * @throws \coding_exception
     */
    protected function get_file_fields() {
        $mform = $this->_form;
        $filefield = [];
        $properties = $this->get_mix_persistent_form_properties();
        foreach ($mform->_elements as $e) {
            $elementtype = $e->getType();
            $elementname = $this->get_real_element_name($e);
            if (in_array($elementtype, ['filemanager', 'file', 'editor'])) {
                $filefield[$elementname] = (object) [
                    'type' => $elementtype,
                    'name' => $elementname,
                    'definition' => $properties[$elementname]
                ];
            }
        }
        return $filefield;
    }

    /**
     * Tweak for editor element names as they are created with _editor suffix
     *
     * @param \HTML_QuickForm_element $e
     * @return false|string
     */
    private function get_real_element_name(\HTML_QuickForm_element $e) {
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
     */
    private static function remove_editor_suffix($fieldname) {
        $EDITOR_SUFFIX = '_editor';
        return (substr($fieldname, -strlen($EDITOR_SUFFIX)) == $EDITOR_SUFFIX) ?
            substr($fieldname, 0, strlen($fieldname) - strlen($EDITOR_SUFFIX)) : $fieldname;
    }

    /**
     * Set the default values for file
     *
     * Either from the optional parameter or the itemid
     *
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function prepare_for_files() {
        $allfilefields = $this->get_file_fields();
        $context = \context_system::instance();
        $component = persistent_utils::get_component(static::$persistentclass);
        $filearearoot = persistent_utils::get_persistent_prefix(static::$persistentclass);
        $item = $this->get_persistent();
        $itemdata = $item->to_record();
        $itemid = $itemdata ? $itemdata->id : 0;

        foreach ($allfilefields as $field) {
            $filearea = $filearearoot . '_' . $field->name;
            if ($field->type == 'filemanager') {
                $options = $this->file_get_option($allfilefields[$field->name]);
                $filemanagerformelt = $field->name;
                $draftitemid = file_get_submitted_draft_itemid($filemanagerformelt);
                file_prepare_draft_area($draftitemid,
                    $context->id,
                    $component,
                    $filearea,
                    $itemid,
                    $options);
                $itemdata->{$filemanagerformelt} = $draftitemid;
            }
            if ($field->type == 'editor') {
                $itemid = $item->get('id');
                $options = $this->editor_get_option($allfilefields[$field->name]);
                file_prepare_standard_editor($itemdata,
                    $field->name,
                    $options,
                    $context,
                    'local_cltools',
                    $filearea,
                    $itemid
                );

            }
        }
        $this->set_data($itemdata);
    }

    /**
     * Save submited files
     *
     * @throws \ReflectionException
     * @throws \dml_exception
     */
    public function save_submitted_files(&$originaldata) {
        $data = \moodleform::get_data();

        $allfilefields = $this->get_file_fields();
        $context = \context_system::instance();
        $component = persistent_utils::get_component(static::$persistentclass);
        $filearearoot = persistent_utils::get_persistent_prefix(static::$persistentclass);
        $itemid = $this->get_persistent()->get('id');
        foreach ($allfilefields as $field) {
            $filearea = $filearearoot . '_' . $field->name;
            if ($field->type == 'filemanager') {
                $options = $this->file_get_option($allfilefields[$field->name]);
                file_save_draft_area_files($data->{$field->name},
                    $context->id,
                    $component,
                    $filearea,
                    $itemid,
                    $options);
            } else if ($field->type == 'editor') {
                $options = $this->editor_get_option($allfilefields[$field->name]);
                $data = file_postupdate_standard_editor($data, $field->name,
                    $options,
                    $context,
                    $component,
                    $filearea,
                    $itemid);
                $originaldata->{$field->name} = $data->{$field->name};
                $originaldata->{$field->name . 'format'} = $data->{$field->name . 'format'};
            } else {
                $this->save_stored_file($field->name,
                    $context->id,
                    $component,
                    $filearea,
                    $itemid,
                    $options);
            }

        }
    }
}