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
use local_cltools\othersimple\entity;

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
class entity_lookup_test extends advanced_testcase {
    /**
     * Setup
     *
     */
    public function setUp() {
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
        }
    }

    /**
     * Test an API function access
     */
    public function test_get_entity_lookup_not_logged_in() {
        $this->expectException('require_login_exception');
        entity_lookup::execute(entity::class, 'shortname');
    }

    /**
     * Test an API function access
     */
    public function test_get_entity_lookup_with_simple_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException('restricted_context_exception');
        entity_lookup::execute(entity::class, 'shortname');
    }

    /**
     * Test an API function access
     */
    public function test_get_entity_lookup_with_admin() {
        $this->setAdminUser(); // This is thanks to the static validate_global_access method.
        entity_lookup::execute(entity::class, 'shortname');
    }

    /**
     * Test an API function
     */
    public function test_get_entity_lookup() {
        $this->setAdminUser();
        $shortnamesapicall = array_values(entity_lookup::execute(entity::class, 'shortname'));
        $this->assertEquals(['Not available', 'Shortname 1', 'Shortname 2'],
                json_decode($shortnamesapicall[0], true));
        $idnumbersapicall = array_values(entity_lookup::execute(entity::class, 'idnumber'));
        $this->assertEquals(['Not available', 'Idnumber 1', 'Idnumber 2'],
                json_decode($idnumbersapicall[0], true));
    }
}
