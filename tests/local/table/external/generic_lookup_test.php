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

/**
 * API test
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_cltools\local\table\external\generic_lookup
 */
class generic_lookup_test extends advanced_testcase {

    /**
     * Test an API function access
     *
     * @covers \local_cltools\local\table\external\generic_lookup
     */
    public function test_get_generic_lookup_not_logged_in() {
        $this->expectException('invalid_parameter_exception');
        generic_lookup::execute('user');
    }

    /**
     * Test an API function access
     *
     * @covers \local_cltools\local\table\external\entity_lookup
     */
    public function test_get_generic_lookup_with_simple_user() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->expectException('required_capability_exception');
        generic_lookup::execute('user');
    }

    /**
     * Test an API function access
     *
     * @covers \local_cltools\local\table\external\generic_lookup
     */
    public function test_get_entity_lookup_with_admin() {
        $this->resetAfterTest();
        $this->setAdminUser(); // This is thanks to the static validate_global_access method.
        generic_lookup::execute('user');
    }

    /**
     * Test an API function
     *
     * @covers \local_cltools\local\table\external\generic_lookup
     */
    public function test_get_generic_lookup_user() {
        $this->resetAfterTest();
        $usersdata = [
                ['firstname' => 'User 1', 'lastname' => 'User 1'],
                ['firstname' => 'User 2', 'lastname' => 'User 2']
        ];
        foreach ($usersdata as $record) {
            $user = $this->getDataGenerator()->create_user($record);
            $users[$user->id] = $user;
        }

        $this->setAdminUser();
        $selectusers = array_values(generic_lookup::execute('user'));
        $this->assertCount(4, $selectusers[0]); // 4 users => Guest, admin and the two others.
        foreach ($selectusers[0] as $user) {
            if ($user['id'] > 2) {
                $this->assertContains(fullname($users[$user['id']]), $user['value']);
                $this->assertContains($users[$user['id']]->email, $user['value']);
            }
        }
    }
}
