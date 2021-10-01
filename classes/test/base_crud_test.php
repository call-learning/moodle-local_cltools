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
 * Persistent utils base test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\test;
defined('MOODLE_INTERNAL') || die();

use local_cltools\simple\entity;

/**
 * Persistent utils test case
 *
 * This will add autoloading for the simple entity class
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_crud_test extends \advanced_testcase {
    /**
     * Setup persistent table
     */
    public function setUp() {
        parent::setUp();
        parent::setUp();
        spl_autoload_register(function() {
            global $CFG;
            require_once($CFG->dirroot . '/local/cltools/tests//fixtures/simple/entity.php');
            require_once($CFG->dirroot . '/local/cltools/tests//fixtures/simple/table.php');
            require_once($CFG->dirroot . '/local/cltools/tests//fixtures/simple/exporter.php');
            require_once($CFG->dirroot . '/local/cltools/tests//fixtures/simple/external.php');
            require_once($CFG->dirroot . '/local/cltools/tests//fixtures/simple/form.php');
        });
        $this->resetAfterTest();
        entity::delete_table();
        entity::create_table();
    }

    /**
     * Remove persistent table
     */
    public function tearDown() {
        entity::delete_table();
        parent::tearDown();
    }
}
