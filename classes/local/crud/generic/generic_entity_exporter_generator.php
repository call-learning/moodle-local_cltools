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

namespace local_cltools\local\crud\generic;

use core\external\exporter;
use core\external\persistent_exporter;
use core\persistent;

defined('MOODLE_INTERNAL') || die();

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
     * @param string $entityclassname
     * @param persistent $entity
     * @return persistent_exporter|mixed
     * @throws \ReflectionException
     */
    public static function generate(string $entityclassname, persistent $entity) {
        $exporter = new class($entity, [], $entityclassname) extends persistent_exporter {
            /**
             * @var string $classname This will be changed dynamically before calling the constructor.
             */
            public static $classname;

            /**
             * Constructor - saves the persistent object, and the related objects.
             *
             * @param \core\persistent $persistent The persistent object to export.
             * @param array $related - An optional list of pre-loaded objects related to this persistent.
             */
            public function __construct(\core\persistent $persistent, $related = array(), string $classname) {
                if (!$persistent instanceof $classname) {
                    throw new coding_exception('Invalid type for persistent. ' .
                            'Expected: ' . $classname . ' got: ' . get_class($persistent));
                }
                $this->persistent = $persistent;

                if (method_exists($this->persistent, 'get_context') && !isset($this->related['context'])) {
                    $this->related['context'] = $this->persistent->get_context();
                }

                $data = $persistent->to_record();
                exporter::__construct($data, $related);
            }
            /**
             * Define the related class.
             *
             * @return string
             */
            protected static function define_class() {
                return self::$classname;
            }
        };
        $exporter::$classname = $entityclassname;
        return $exporter;
    }


}

;
