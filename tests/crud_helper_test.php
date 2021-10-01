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
 * CRUD Helper class tests.
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools;
// See https://docs.moodle.org/dev/Coding_style#Namespaces_within_.2A.2A.2Ftests_directories.
defined('MOODLE_INTERNAL') || die();

use local_cltools\local\crud\helper\base as crud_helper;
global $CFG;
require_once($CFG->dirroot . '/local/cltools/tests/lib.php');
use local_cltools\simple\entity;

/**
 * CRUD Helper class tests.
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cltools_crud_helper_test extends \advanced_testcase {
    /**
     * Setup persistent table
     */
    public function setUp() {
        parent::setUp();
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
