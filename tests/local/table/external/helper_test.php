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

namespace local_cltools\local\table\external;

use advanced_testcase;
use core_table\local\filter\filter;
use local_cltools\local\filter\string_filter;
use local_cltools\simple\entity;
use local_cltools\simple\table;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');

require_once($CFG->dirroot . '/local/cltools/tests/lib.php');

/**
 * API test
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_test extends advanced_testcase {
    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        entity::delete_table();
        entity::create_table();
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\helper::get_table_handler_instance
     */
    public function test_get_table_handler() {
        $instance = helper::get_table_handler_instance(table::class, "", '', true, []);
        $this->assertNotNull($instance);
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\helper::setup_filters
     */
    public function test_set_filters() {
        $instance = helper::get_table_handler_instance(table::class, "", '', true, []);
        $filterset = [
                [
                        'name' => 'shortname',
                        'type' => 'string_filter',
                        'required' => true,
                        'jointype' => filter::JOINTYPE_ALL,
                        'values' => ['Short...'],
                ],
        ];
        helper::setup_filters($instance, $filterset, filter::JOINTYPE_ALL);
        $this->assertNotNull($instance->get_filterset());
        $filterset = $instance->get_filterset();
        $this->assertJsonStringEqualsJsonString(
                '{ "shortname":{
         "type":"string_filter",
         "name":"shortname",
         "jointype":2,
         "values":[
            "\"Short...\""
         ]
      }}',
                json_encode($filterset->get_filters()));
    }
}
