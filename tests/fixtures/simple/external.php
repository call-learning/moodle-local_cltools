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
 * Rotation entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\simple;

use external_api;
use external_multiple_structure;
use external_single_structure;
use local_cltools\local\crud\entity_utils;

defined('MOODLE_INTERNAL') || die();

global $CFG;


/**
 * Class rotation
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    public static function get_rotations_parameters() {
        return entity_utils::external_get_filter_generic_parameters();
    }

    public static function get_rotations($filters) {
        $inputparams = compact($filters);
        $params = self::validate_parameters(self::get_rotations_parameters(), $inputparams);
    }

    public static function get_rotations_returns() {
        return new external_multiple_structure(
            exporter::get_read_structure()
        );
    }

    public static function create_rotation_parameters() {
        return new \external_function_parameters(
            [
                'rotation' => exporter::get_create_structure()
            ]
        );
    }

    public static function create_rotation($rotation) {
        global $PAGE;
        $inputparams = compact($rotation);
        $params = self::validate_parameters(self::create_rotation_parameters(), $inputparams);
        $rotation = $params['rotation'];
        $rotation = new entity(0, $rotation);
        $rotation->save();
        $output = $PAGE->get_renderer('local_cltools');
        return (new exporter($rotation))->export($output);
    }

    public static function create_rotation_returns() {
        return new external_single_structure(
            ['rotation' => exporter::get_read_structure()]
        );
    }
}
