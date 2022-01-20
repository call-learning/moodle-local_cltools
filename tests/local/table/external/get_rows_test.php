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
use local_cltools\othersimple\entity as otherentity;
use local_cltools\simple\entity;
use local_cltools\simple\table;
use stdClass;

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
class get_rows_test extends advanced_testcase {
    /**
     * @var stdClass $entities
     */
    protected $entities;

    /**
     * Setup
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        entity::delete_table();
        entity::create_table();
        otherentity::delete_table();
        otherentity::create_table();
        // Create a couple of entities.
        $otherentities = [];
        $otherentitiesdata = [
                (object) [
                        'shortname' => 'Shortname 1',
                        'idnumber' => 'Idnumber 1',
                        'sortorder' => 0,
                ],
                (object) [
                        'shortname' => 'Shortname 2',
                        'idnumber' => 'Idnumber 2',
                        'sortorder' => 0,
                ]
        ];

        foreach ($otherentitiesdata as $entityrecord) {
            $entity = new otherentity(0, $entityrecord);
            $entity->save();
            $otherentities[] = $entity;
        }
        $entitiesdata = [
                (object) [
                        'shortname' => 'Shortname 1',
                        'idnumber' => 'Idnumber 1',
                        'description' => 'Description....',
                        'sortorder' => 1,
                        'parentid' => 0,
                        'othersimpleid' => $otherentities[0]->get('id'),
                        'scaleid' => 0
                ],
                (object) [
                        'shortname' => 'Shortname 2',
                        'idnumber' => 'Idnumber 2',
                        'description' => 'Description....',
                        'sortorder' => 2,
                        'parentid' => 0,
                        'othersimpleid' => $otherentities[1]->get('id'),
                        'scaleid' => 0
                ]
        ];
        foreach ($entitiesdata as $entityrecord) {
            $entity = new entity(0, $entityrecord);
            $entity->save();
            $this->entities[] = $entity->to_record();
        }
    }

    /**
     * Test an API function
     */
    public function test_get_rows_not_logged_in() {
        $this->expectException('require_login_exception');
        get_rows::execute(table::class, "", '');
    }

    /**
     * Test an API function
     */
    public function test_get_rows_simple() {
        $this->setAdminUser();
        $rows = get_rows::execute(table::class, "", '');
        $this->assertCount(2, $rows['data']);
        $rowdata = array_map(function($row) {
            return json_decode($row);
        }, $rows['data']);
        $expected = [
                (object) [
                        'shortname' => 'Shortname 1',
                        'idnumber' => 'Idnumber 1',
                        'description' => 'Description....',
                        'parentid' => '0',
                        'path' => '',
                        'sortorder' => '1',
                        'othersimpleid' => '1',
                        'scaleid' => '0',
                        'image' => '0',
                        'id' => '1',
                        'actions' => $rowdata[0]->actions,
                ],
                (object) [
                        'shortname' => 'Shortname 2',
                        'idnumber' => 'Idnumber 2',
                        'description' => 'Description....',
                        'parentid' => '0',
                        'path' => '',
                        'sortorder' => '2',
                        'othersimpleid' => '2',
                        'scaleid' => '0',
                        'image' => '0',
                        'id' => '2',
                        'actions' => $rowdata[1]->actions,
                ],
        ];

        $this->assertContains('<a href="edit.php?id=', $rowdata[0]->actions);
        $this->assertEquals($expected, $rowdata);
    }
}
