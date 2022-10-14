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
use local_cltools\local\crud\form\entity_form;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent generic entity form
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generic_entity_form_generator {
    /**
     * Generate form
     *
     * @param string $entityclassname
     * @param array $args
     * @return entity_form|mixed
     * @throws \ReflectionException
     */
    public static function generate(string $entityclassname, $args) {
        $form = new class() extends entity_form {
            /** @var string The fully qualified classname. */
            protected static $persistentclass = '';
        };
        $refclass = new \ReflectionClass($form);
        $refclass->setStaticPropertyValue('persistentclass', $entityclassname);
        return $refclass->newInstanceArgs($args);
    }
}
