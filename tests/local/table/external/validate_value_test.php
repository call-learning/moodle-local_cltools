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
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');

require_once($CFG->dirroot . '/local/cltools/tests/lib.php');

use local_cltools\othersimple\entity;
use local_cltools\othersimple\table;

use advanced_testcase;
/**
 * API test
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_value_test extends advanced_testcase {
    /**
     * @var array $entities
     */
    protected $entities = [];

    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        entity::delete_table();
        entity::create_table();
        // Create a couple of entities.
        $entitiesdata = [
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
        foreach ($entitiesdata as $entityrecord) {
            $entity = new entity(0, $entityrecord);
            $entity->save();
            $this->entities[] = $entity->to_record();
        }
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\validate_value::execute
     */
    public function test_set_value_not_logged_in() {
        $this->expectException('require_login_exception');
        validate_value::execute(table::class, "", '1234', $this->entities[0]->id, 'shortname',
                json_encode('Shortname 3'));
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\validate_value::execute
     */
    public function test_set_value_logged_in() {
        $this->setAdminUser();
        $returnval = validate_value::execute(table::class, "", '1234', $this->entities[0]->id,
                'shortname',
                json_encode('Shortname 3'));
        $this->assertTrue($returnval['success']);
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\validate_value::execute
     */
    public function test_set_value_logged_in_no_user_right() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $returnval = validate_value::execute(table::class, "", '1234', $this->entities[0]->id,
                'shortname',
                json_encode('Shortname 3'));
        $this->assertFalse($returnval['success']);
    }
}
