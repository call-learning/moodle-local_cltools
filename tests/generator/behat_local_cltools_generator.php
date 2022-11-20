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
 * Behat data generator for local_cltools.
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_cltools_generator extends behat_generator_base {
    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
                'entities' => [
                        'singular' => 'entity',
                        'datagenerator' => 'entity',
                        'required' => ['entitynamespace'],
                        'switchids' => ['scale' => 'scaleid'],
                ]
        ];
    }

    /**
     * Process entity creation
     *
     * @param array $data Raw data.
     * @return array Processed data.
     */
    protected function process_entity(array $data) {
        $entityclass = $data['entitynamespace'] . '\\entity';
        unset($data['entitynamespace']);
        if (!class_exists($entityclass)) {
            global $CFG;
            require_once($CFG->dirroot . '/local/cltools/tests/lib.php');
            if (!class_exists($entityclass)) {
                $entityclass = "\\local_cltools\\$entityclass";
                if (!class_exists($entityclass)) {
                    throw new Exception("Cannot find the specified entity class $entityclass");
                }
            }
        }
        return $this->componentdatagenerator->create_entity($entityclass, (object) $data);
    }
}
