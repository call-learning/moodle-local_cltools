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
     * A set of basic entity data
     */
    const BASIC_ENTITITES_DATA = [
            [
                    'shortname' => 'Shortname 1',
                    'idnumber' => 'Idnumber 1',
                    'description' => 'Description for B....',
                    'sortorder' => 1,
                    'parentid' => 0,
                    'scaleid' => 0
            ],
            [
                    'shortname' => 'Shortname 2',
                    'idnumber' => 'Idnumber 2',
                    'description' => 'Description for A....',
                    'sortorder' => 2,
                    'parentid' => 0,
                    'scaleid' => 0
            ]
    ];
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
        $entitiesdata = self::BASIC_ENTITITES_DATA;
        $entitiesdata[0]['othersimpleid'] = $otherentities[0]->get('id');
        $entitiesdata[1]['othersimpleid'] = $otherentities[1]->get('id');
        foreach ($entitiesdata as $entityrecord) {
            $entity = new entity(0, (object) $entityrecord);
            $entity->save();
            $this->entities[] = $entity->to_record();
        }
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\get_rows::execute
     */
    public function test_get_rows_not_logged_in() {
        $this->expectException('require_login_exception');
        get_rows::execute(table::class, "", '1234');
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\get_rows::execute
     */
    public function test_get_rows_simple() {
        $this->setAdminUser();
        $rows = get_rows::execute(table::class, "", '1234');
        $this->assertCount(2, $rows['data']);
        $rowdata = array_map(function($row) {
            return json_decode($row);
        }, $rows['data']);

        $this->assertContains('<a href="edit.php?id=', $rowdata[0]->actions);
        $expected = self::BASIC_ENTITITES_DATA;
        $expected[0]['othersimpleid'] = 1;
        $expected[1]['othersimpleid'] = 2;
        foreach ($expected as $index => $expecteddata) {
            foreach ($expecteddata as $key => $value) {
                $this->assertEquals($value, $rowdata[$index]->$key);
            }
        }
    }

    /**
     * Test an API function with pagination
     *
     * @covers \local_cltools\local\table\external\get_rows::execute
     */
    public function test_get_rows_pagination() {
        // Add more rows.
        $pagesize = 5;
        for ($i = 0; $i < $pagesize * 2; $i++) {
            $entityrecord = (object) [
                    'shortname' => "New entity $i",
                    'idnumber' => "New entity $i",
                    'description' => "Description $i....",
                    'sortorder' => 1,
                    'parentid' => 0,
                    'othersimpleid' => $this->entities[0]->id,
                    'scaleid' => 0
            ];
            $entity = new entity(0, $entityrecord);
            $entity->save();
        }
        $this->setAdminUser();
        $retval = get_rows::execute(table::class, "", '1234', [], [], 0, false, [], [], 1, $pagesize);
        $this->assertCount($pagesize, $retval['data']);
        $retval = get_rows::execute(table::class, "", '1234', [], [], 0, false, [], [], 2, $pagesize);
        $this->assertCount($pagesize, $retval['data']);
        $this->assertContains("New entity 3", $retval['data'][0]); // First item is the New entity 3.
    }

    /**
     * Test an API function with data sort
     *
     * @param array $sortorder
     * @param array $expectedresult
     *
     * @covers       \local_cltools\local\table\external\get_rows::execute
     * @dataProvider sort_data_provider
     */
    public function test_get_rows_sort_data($sortorder, $expectedresult) {
        $this->setAdminUser();
        $retval = get_rows::execute(table::class, "", '1234', $sortorder);
        $firstval = json_decode($retval['data'][0]);
        foreach ($expectedresult as $result) {
            $this->assertEquals($firstval->{$result['field']}, $result['value']);
        }
    }

    /**
     * Sort order values and expected results
     *
     * @return array[]
     */
    public function sort_data_provider() {
        return [
                'sort simple asc' => [
                        'sortorder' => [
                                [
                                        'sortby' => 'sortorder',
                                        'sortorder' => 'ASC'
                                ]
                        ],
                        'expectedfirstresult' => [
                                ['field' => 'sortorder', 'value' => 1]
                        ]
                ],
                'sort simple desc' => [
                        'sortorder' => [
                                [
                                        'sortby' => 'sortorder',
                                        'sortorder' => 'DESC'
                                ]
                        ],
                        'expectedfirstresult' => [
                                ['field' => 'sortorder', 'value' => 2]
                        ]
                ],
                'sort compound asc' => [
                        'sortorder' => [
                                [
                                        'sortby' => 'description',
                                        'sortorder' => 'ASC'
                                ],
                                [
                                        'sortby' => 'sortorder',
                                        'sortorder' => 'DESC'
                                ],
                        ],
                        'expectedfirstresult' => [
                                ['field' => 'description', 'value' => 'Description for A....']
                        ]
                ],
        ];
    }
}
