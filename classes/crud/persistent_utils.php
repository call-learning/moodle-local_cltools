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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\crud;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();
global $CFG;

class persistent_utils {
    /**
     * @param \ReflectionClass| string $persistentclass
     * @return string
     * @throws \ReflectionException
     */
    public static function get_persistent_prefix($persistentclass) {
        if (is_string($persistentclass)) {
            $persistentclass = new \ReflectionClass($persistentclass);
        }
        $namespace = $persistentclass->getNamespaceName();
        $namespaceparts = explode('\\', $namespace);
        return strtolower(end($namespaceparts));
    }

    const RESERVED_PROPERTIES = array('id', 'timecreated', 'timemodified', 'usermodified');

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
     * Get associated images
     *
     * @param int $pageid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_files_images_urls($entityid, $filearea) {
        $contextsystemid = \context_system::instance()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextsystemid,
            persistent_utils::PLUGIN_COMPONENT_NAME,
            $filearea,
            $entityid);
        $imagesurls = [];
        foreach ($files as $image) {
            if ($image->is_valid_image()) {
                $imagesurls[] = \moodle_url::make_pluginfile_url(
                    $contextsystemid,
                    persistent_utils::PLUGIN_COMPONENT_NAME,
                    $filearea,
                    $entityid,
                    $image->get_filepath(),
                    $image->get_filename()
                );
            }
        }
        return $imagesurls;
    }

    const PLUGIN_FILE_AREAS_IMAGE = array('rotation');
    const PLUGIN_COMPONENT_NAME = 'local_cltools';
}