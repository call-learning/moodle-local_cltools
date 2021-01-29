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
namespace local_cltools; // https://docs.moodle.org/dev/Coding_style#Namespaces_within_.2A.2A.2Ftests_directories.
defined('MOODLE_INTERNAL') || die();

use local_cltools\local\crud\helper\base as crud_helper;

global $CFG;
require_once(__DIR__.'/lib.php');

/**
 * CRUD Helper class tests.
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cltools_crud_helper_testcase extends \advanced_testcase {
    /**
     * Setup persistent table
     */
    public function setUp() {
        $this->resetAfterTest();
        \local_cltools\local\simple\entity::create_table(true);
    }

    public function tearDown() {
        \local_cltools\local\simple\entity::delete_table();
        parent::tearDown();
    }

    public function test_get_crud_default() {
        $crudmgmt = crud_helper::create(\local_cltools\local\simple\entity::class);
        $this->assertNotNull($crudmgmt);
    }
    public function test_get_persisten_prefix() {
        $crudmgmt = crud_helper::create(\local_cltools\local\simple\entity::class);
        $this->assertEquals('simple', $crudmgmt->get_persistent_prefix());
    }
    public function test_instanciate_related_form() {
        global $PAGE;
        $crudmgmt = crud_helper::create(\local_cltools\local\simple\entity::class);
        $form = $crudmgmt->instanciate_related_form();
        $this->assertNotNull($form);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Make sure we have a logged in user.
        $PAGE->set_url(new \moodle_url('/'));
        $formdisplay = $form->render();
        $this->assertStringContainsString('input',$formdisplay);
    }
    public function test_instanciate_related_persistent_list() {
        global $OUTPUT, $PAGE;
        // Create a couple of entities.
        $entitiesdata = [
            (object) [
                'shortname'=> 'Shortname 1',
                'idnumber'=> 'Idnumber 1',
                'sortorder'=> 0,
            ],
            (object) [
                'shortname'=> 'Shortname 2',
                'idnumber'=> 'Idnumber 2',
                'sortorder'=> 0,
            ]
        ];
        foreach($entitiesdata as $entityrecord) {
            $entity = new \local_cltools\local\simple\entity(0, $entityrecord);
            $entity->save();
        }

        $crudmgmt = crud_helper::create(\local_cltools\local\simple\entity::class);
        $persistentlist = $crudmgmt->instanciate_related_persistent_list();
        $this->assertNotNull( $persistentlist );
        $PAGE->set_url(new \moodle_url('/'));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Make sure we have a logged in user.
        $persistentlist->define_baseurl($PAGE->url);
        ob_start();
        $persistentlist->out(1000, true);
        $listdisplay = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('Shortname 1', $listdisplay);
        $this->assertStringContainsString('Shortname 2', $listdisplay);

    }
}
