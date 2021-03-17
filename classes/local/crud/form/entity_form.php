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

use local_cltools\local\crud\entity_utils;
use local_cltools\local\field\base;
use stdClass;

// Custom form element types
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
 * @package    core
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity_form extends \core\form\persistent {

    /** @var array Fields to remove when getting the final data. */
    protected static $fieldstoremove = array('submitbutton');

    protected $fields = [];

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
        $this->build_fields_info();
        // TODO: at some point we will need to get rid of persistentclass as static.
        // The only exception right not if the provided persistent is null...

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
    protected static function get_fields_definition() {
        return array();
    }

    /**
     * @param \MoodleQuickForm $mform
     * Additional definitions for the form (after we add the fields)
     */
    protected function post_field_definitions(&$mform) {
    }

    /**
     * @param \MoodleQuickForm $mform
     * Additional definitions for the form (before we add the fields)
     */
    protected function pre_field_definitions(&$mform) {
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
        foreach($this->fields as $field) {
            $field->filter_data_for_persistent($filtereddata);
        }
        if(!empty($filtereddata->submitbutton)) {
            unset($filtereddata->submitbutton);
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
        $properties = (static::$persistentclass)::properties_definition();
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
     * Get dynamic form properties from either form definition or directly from the persistent
     *
     * @throws \coding_exception
     */
    protected function build_fields_info() {
        $persistentprops = (static::$persistentclass)::properties_definition();
        $formproperties = $this->get_fields_definition();
        // Merge array recursively so to get the values from the
        // Form definition last.
        $allproperties = array_merge($persistentprops, $formproperties);
        $allproperties = array_map(
            function($prop) {
                $prop['rawtype'] = empty($prop['type']) ? PARAM_RAW : $prop['type'];
                unset($prop['type']); // Type will be guessed if not set in the format.
                $format = !empty($prop['format']) ? $prop['format'] : [];
                $prop = array_merge($prop, $format); // We make sure that the raw type is the
                return $prop;
            },
            $allproperties
        );

        foreach ($allproperties as $name => $format) {

            if (strpos($name, 'format') > 0 ) {
                continue; // Skip fields with description in their name.
            }
            $fieldinfo = array_merge(
                [
                    'rawtype' => PARAM_RAW,
                    'fieldname' => $name
                ],
                !empty($allproperties[$name]) ? $allproperties[$name] : []
            );
            if (in_array($name.'format', array_keys($allproperties))) {
                $fieldinfo['type'] = 'editor';
            }
            $possibledefault = '';
            $field = null;
            if (entity_utils::is_reserved_property($name)) {
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
                $fieldinfo['rawtype'] = PARAM_INT;
                $fieldinfo['type'] = 'hidden';
                $fieldinfo['default'] = $possibledefault;
            }
            $fieldinfo['filemanageroptions'] = $this->filemanager_get_default_options($fieldinfo);
            $fieldinfo['editoroptions'] = $this->editor_get_option($fieldinfo);

            if (!empty($fieldinfo['type'])) {
                if (empty($fieldinfo['fullname'])) {
                    $fieldinfo['fullname'] = entity_utils::get_string_for_entity(static::$persistentclass, $name);
                }
            }
            $field = base::get_instance_from_def($name, $fieldinfo);
            $this->fields[$name] = $field; // Update it.
        }
    }

    /**
     * The form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $this->pre_field_definitions($mform);
        foreach ($this->fields as $name => $field) {
            $field->add_form_element($mform);
        }
        $this->post_field_definitions($mform);
        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Get options for editor
     *
     * @param $fieldinfo
     * @return array
     * @throws \dml_exception
     */
    protected function editor_get_option($fieldinfo) {
        global $CFG;
        $formoptions =
            [
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'noclean' => true,
                'context' => \context_system::instance(),
                'enable_filemanagement' => true,
                'changeformat' => true
            ];
        $formoptions =
            empty($forminfo['editoroptions']) ? $formoptions : array_merge($formoptions, $fieldinfo['editoroptions']);
        return $formoptions;
    }

    /**
     * Get options for filemanager
     *
     * @param $fieldinfo
     * @return array
     * @throws \dml_exception
     */
    protected function filemanager_get_default_options(&$fieldinfo) {
        $formoptions = ['context' => \context_system::instance()];
        return $formoptions;
    }

    /**
     * Get all fields related to file
     *
     * @return array
     * @throws \coding_exception
     */
    protected function get_file_fields_info() {
        static $fields = [];
        if (!empty($fields)) {
            return $fields;
        }
        $mform = $this->_form;
        $fields = [];
        foreach ($mform->_elements as $e) {
            $elementtype = $e->getType();
            $elementname = $this->get_real_element_name($e);
            if (in_array($elementtype, ['filemanager', 'file', 'editor'])) {
                $fields[$elementname] = $this->fields[$elementname];
            }
        }
        return $fields;
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
        $context = \context_system::instance();
        $component = entity_utils::get_component(static::$persistentclass);
        $filearearoot = entity_utils::get_persistent_prefix(static::$persistentclass);
        $item = $this->get_persistent();
        $itemdata = $item->to_record();
        $itemid = $itemdata ? $itemdata->id : 0;

        foreach ($this->fields as $fieldname => $field) {
            $filearea = $filearearoot . '_' . $fieldname;
            $field->prepare_files($itemdata, $context, $component, $filearea, $itemid);
        }
        $this->set_data($itemdata);
    }

    /**
     * Save submited files
     *
     * @throws \ReflectionException
     * @throws \dml_exception
     */
    protected function save_submitted_files() {
        $data = \moodleform::get_data();
        $context = \context_system::instance();
        $component = entity_utils::get_component(static::$persistentclass);
        $filearearoot = entity_utils::get_persistent_prefix(static::$persistentclass);
        $itemid = $this->get_persistent()->get('id');
        foreach ($this->fields as $fieldname => $field) {
            $filearea = $filearearoot . '_' . $fieldname;
            $field->save_files($data, $context, $component, $filearea, $itemid);
        }
        return $data;
    }

    /**
     * Save current data in persitent
     * @param $data
     * @throws \coding_exception
     */
    public function save_data(){
        $data = $this->save_submitted_files();
        $persistentdata = $this->filter_data_for_persistent($data);
        $persistent = $this->get_persistent();
        $persistent->from_record((object) $persistentdata);
        $persistent->save();
    }
}
