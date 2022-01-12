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
 * Persistent utils class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;

use coding_exception;
use context;
use context_system;
use core_component;
use dml_exception;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use lang_string;
use moodle_url;
use ReflectionClass;
use ReflectionException;

defined('MOODLE_INTERNAL') || die();
global $CFG;

class entity_utils {

    /**
     * Some kewords are forbidden in a query.
     */
    const SQL_FORBIDDEN_KEWORD_PREFIX_CHANGE = [
            'group' => 'grp',
            'select' => 'slct',
            'from' => 'frm',
    ];
    const RESERVED_PROPERTIES = array('id', 'timecreated', 'timemodified', 'usermodified');
    /**
     * Default plugin component name
     */
    const DEFAULT_PLUGIN_COMPONENT_NAME = 'local_cltools';

    /**
     * Get string for given entity
     *
     * @param $persistentclass
     * @param $stringname
     * @params $args
     * @return lang_string|string
     * @throws ReflectionException
     * @throws coding_exception
     */
    public static function get_string_for_entity($persistentclass, $stringname, $args = null) {
        $entityprefix = self::get_persistent_prefix($persistentclass);
        $component = self::get_component($persistentclass);
        $stringmanager = get_string_manager();
        $label = '';
        if ($stringmanager->string_exists($entityprefix . ':' . $stringname, $component)) {
            $label = get_string($entityprefix . ':' . $stringname, $component, $args);
        } else if ($stringmanager->string_exists($stringname, $component, $args)) {
            $label = get_string($stringname, $component, $args);
        } else {
            $label = $stringname;
        }
        return $label;
    }

    /**
     * @param ReflectionClass| string $persistentclass
     * @return string
     * @throws ReflectionException
     */
    public static function get_persistent_prefix($persistentclass) {
        $namespace = static::get_persistent_namespace($persistentclass);
        $namespaceparts = explode('\\', $namespace);
        $persistentprefix = strtolower(end($namespaceparts));
        if (!empty(self::SQL_FORBIDDEN_KEWORD_PREFIX_CHANGE[$persistentprefix])) {
            $persistentprefix = self::SQL_FORBIDDEN_KEWORD_PREFIX_CHANGE[$persistentprefix];
        }
        return $persistentprefix;
    }

    /**
     * Get persistent namespace
     *
     * @param ReflectionClass| string $persistentclass
     * @return string
     * @throws ReflectionException
     */
    public static function get_persistent_namespace($persistentclass) {
        if (is_string($persistentclass)) {
            $persistentclass = new ReflectionClass($persistentclass);
        }
        $namespace = $persistentclass->getNamespaceName();
        return $namespace;
    }

    /**
     * Guess the component the persistent class belongs to (from its namespace)
     *
     * @param $persistentclass
     * @throws ReflectionException
     */
    public static function get_component($persistentclass) {
        $namespace = static::get_persistent_namespace($persistentclass);
        $namespacecomp = explode('\\', $namespace);
        $component = self::DEFAULT_PLUGIN_COMPONENT_NAME;
        if ($namespacecomp && count($namespacecomp) > 0) {
            if (core_component::is_valid_plugin_name(null, $namespacecomp[0])) {
                $component = core_component::normalize_componentname($namespacecomp[0]);
            }
        }
        return $component;
    }

    /**
     * Is a reserved property
     *
     * @param $propertyname
     * @return bool
     */
    public static function is_reserved_property($propertyname) {
        return in_array($propertyname, self::RESERVED_PROPERTIES);
    }

    /**
     * Generic filter parameters (common to activities and courses)
     *
     * @return external_function_parameters
     */
    public static function external_get_filter_generic_parameters() {
        return new external_function_parameters(
                array(
                        'filters' => new external_multiple_structure (
                                new external_single_structure(
                                        array(
                                                'type' => new external_value(PARAM_ALPHANUM,
                                                        'Type of filter'),
                                                'shortname' => new external_value(PARAM_ALPHANUM,
                                                        'Shortname of the field to search for',
                                                        VALUE_OPTIONAL),
                                                'operator' => new external_value(PARAM_INT,
                                                        'This will be EQUAL, CONTAINS, NOTEQUAL...'),
                                                'value' => new external_value(PARAM_RAW, 'The value of the filter to look for.')
                                        )
                                ),
                                'Filter the results',
                                VALUE_OPTIONAL
                        ),
                        'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                        'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                        'sorting' => new external_multiple_structure(
                                new external_single_structure(
                                        array(
                                                'column' => new external_value(PARAM_ALPHANUM,
                                                        'Column name for the sorting'),
                                                'order' => new external_value(PARAM_ALPHA,
                                                        'ASC for ascending, DESC for descending, ascending by default'
                                                ),
                                        )
                                ),
                                'Sort the results',
                                VALUE_OPTIONAL
                        ),
                )
        );
    }

    /**
     * Get associated files urls
     *
     * @param int $entityid
     * @param string $filearea
     * @param string $component
     * @param context $context
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_files_urls($entityid, $filearea, $component = null, $context = null) {
        $context = empty($context) ? context_system::instance() : $context;
        $files = static::get_files($entityid, $filearea, $component, $context);
        $imagesurls = [];
        foreach ($files as $image) {
            if ($image->is_valid_image()) {
                $imagesurls[] = moodle_url::make_pluginfile_url(
                        $context->id,
                        $component,
                        $filearea,
                        $entityid,
                        $image->get_filepath(),
                        $image->get_filename()
                );
            }
        }
        return $imagesurls;
    }

    /**
     * Get associated files
     *
     * @param int $entityid
     * @param string $filearea
     * @param string $component
     * @param context $context
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_files($entityid, $filearea, $component = null, $context = null) {
        $contextid = empty($context) ? context_system::instance()->id : $context->id;
        $component = $component ? $component : self::DEFAULT_PLUGIN_COMPONENT_NAME;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid,
                $component,
                $filearea,
                $entityid);
        return $files;
    }

    /**
     * Check if the property is required
     *
     * The wording "null"/"null allowed" is confusing so we use this method as a way to make
     * it less ambiguous.
     *
     * @param array $prop
     * @return bool
     */
    public static function is_property_required($prop) {
        return !empty($prop['null'])
                && ($prop['null'] == NULL_NOT_ALLOWED);
    }

    /**
     * Get fields defined and check if tnery
     *
     * @param string $persistentclassname
     * @return mixed
     * @throws coding_exception
     */
    public static function get_defined_fields($persistentclassname) {
        $interfaces = class_implements($persistentclassname);
        if (empty($interfaces[enhanced_persistent::class])) {
            throw new coding_exception('This class should implemented enhanced_persistent interface');
        }
        return $persistentclassname::define_fields();
    }
}
