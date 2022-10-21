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
 * Persistent generic entity exporter
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\generic;

use core\external\persistent_exporter;
use core\persistent;
use local_cltools\local\crud\entity_exporter;

/**
 * Persistent generic entity exporter
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generic_entity_exporter_generator {
    /**
     * Generate exporter
     *
     * @param persistent $entity
     * @return persistent_exporter
     */
    public static function generate(persistent $entity): entity_exporter {
        $exporter = new class($entity) extends entity_exporter {
            /**
             * @var string $classname This will be changed dynamically after calling the constructor.
             */
            public static $classname;

            /**
             * Define the related class.
             *
             * @return string
             */
            protected static function define_class() {
                return self::$classname;
            }
        };
        $exporter::$classname = get_class($entity);
        return $exporter;
    }

}

