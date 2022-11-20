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
namespace local_cltools\local\crud;

use coding_exception;
use context_system;
use core\external\exporter;
use core\persistent;
use core_user\output\myprofile\renderer;
use local_cltools\local\field\persistent_field;
use moodle_url;
use renderer_base;

/**
 * Class persistent_exporter
 *
 * This is like persistent_exporter but for entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity_exporter extends exporter {

    /** @var persistent $persistent The persistent object we will export. */
    protected $persistent = null;
    /**
     * Persistent component
     *
     * @var string|null $persistentcomponent
     */
    protected $persistentcomponent = null;

    /**
     * @var int|null $instanceid
     */
    protected $instanceid = null;

    /**
     * @var array $fields
     */
    protected $fields = [];

    /**
     * persistent_exporter constructor.
     *
     * @param persistent $persistent
     * @param array $related
     */
    public function __construct(persistent $persistent, array $related = array()) {
        $this->persistentcomponent = entity_utils::get_component(new \ReflectionClass($persistent));
        $this->instanceid = (int) $persistent->get('id');
        $related = array_merge($related,
                [
                        'context' => context_system::instance(),
                        'component' => $this->persistentcomponent,
                        'itemid' => (int) $persistent->get('id')
                ]
        );
        $this->persistent = $persistent;

        if (method_exists($this->persistent, 'get_context') && !isset($this->related['context'])) {
            $this->related['context'] = $this->persistent->get_context();
        }

        $data = $persistent->to_record();
        parent::__construct($data, $related);
    }

    /**
     * Define related object information
     *
     * @return string[]
     */
    protected static function define_related() {
        return array('context' => '\\context', 'component' => 'string?', 'itemid' => 'int?');
    }

    /**
     * Define other properties
     *
     * @return array|array[]
     * @throws coding_exception
     */
    protected static function define_other_properties() {
        $fields = entity_utils::get_defined_fields(static::define_class());
        $props = [
                'usermodifiedfullname' => [
                        'type' => PARAM_ALPHANUMEXT
                ]
        ];
        foreach ($fields as $field) {
            /* @var persistent_field $field a persistent field */
            $props[$field->get_name() . 'formatted'] = [
                    'type' => $field->get_raw_param_type()
            ];

        }
        return $props;
    }

    /**
     * Returns the specific class the persistent should be an instance of.
     *
     * @return string
     */
    abstract protected static function define_class();

    /**
     * Persistent exporters get their standard properties from the persistent class.
     *
     * @return array Keys are the property names, and value their definition.
     */
    protected static function define_properties() {
        $fields = entity_utils::get_defined_fields(static::define_class());
        $properties['timecreated'] = array(
                'default' => 0,
                'type' => PARAM_INT,
        );
        $properties['timemodified'] = array(
                'default' => 0,
                'type' => PARAM_INT
        );
        $properties['usermodified'] = array(
                'default' => 0,
                'type' => PARAM_INT
        );
        foreach ($fields as $field) {
            $properties = array_merge($properties, $field->get_persistent_properties());
        }
        return $properties;
    }

    /**
     * Get value for persistent fields
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output) {
        $values = [];
        $fields = entity_utils::get_defined_fields(static::define_class());
        foreach ($fields as $fielname => $field) {
            /* @var persistent_field $field a persistent field */
            $values[$field->get_name() . 'formatted'] =
                    $field->format_value($this->persistent, $output);
        }
        $values['usermodifiedfullname'] = '';
        if ($this->data->usermodified) {
            $usermodified = \core_user::get_user($this->data->usermodified);
        }
        $values['usermodifiedfullname'] = fullname($usermodified);
        return $values;
    }

    /**
     * Export linked file
     *
     * @param string $filearea
     * @param string|null $fileprefix
     * @param string|null $filetypegroup
     * @return moodle_url|null
     */
    protected function export_file($filearea, $fileprefix = null, $filetypegroup = null) {
        // Retrieve the file from the Files API.
        $files = entity_utils::get_files($this->instanceid, $filearea, $this->persistentcomponent, $this->related['context']);
        $returnedfiled = null;
        foreach ($files as $file) {
            $foundfile = $fileprefix && strpos($file->get_filename(), $fileprefix) !== false;
            $foundfile = $foundfile || ($filetypegroup &&
                            file_mimetype_in_typegroup($file->get_mimetype(), $filetypegroup));
            if ($foundfile) {
                $returnedfiled = $file;
                break;
            }
        }
        if (!$returnedfiled) {
            return null;
        }
        return moodle_url::make_pluginfile_url(
                $this->related['context']->id,
                $this->persistentcomponent,
                $filearea,
                $returnedfiled->get_itemid(),
                $returnedfiled->get_filepath(),
                $returnedfiled->get_filename()
        );
    }
}
