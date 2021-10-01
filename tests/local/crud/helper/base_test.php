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
 * Persistent utils test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;

defined('MOODLE_INTERNAL') || die();

use local_cltools\local\crud\helper\base;
use local_cltools\local\crud\helper\crud_add;
use local_cltools\local\crud\helper\crud_delete;
use local_cltools\local\crud\helper\crud_edit;
use local_cltools\local\crud\helper\crud_list;
use local_cltools\simple\entity;
use local_cltools\test\base_crud_test;

/**
 * Persistent utils test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_test extends base_crud_test {
    /**
     * Get crud management
     */
    public function test_get_crud_default() {
        $crudmgmt = base::create(entity::class);
        // Add by default.
        $this->assertNotNull($crudmgmt);
        $this->assertEquals('local_cltools\local\crud\helper\crud_add', get_class($crudmgmt));
    }

    /**
     * Test that we target the right clas
     */
    public function test_get_persistent_prefix() {
        $crudmgmt = base::create(entity::class);
        $this->assertEquals('simple', $crudmgmt->get_persistent_prefix());
    }

    public function test_instanciate_related_form() {
        global $PAGE;
        $crudmgmt = base::create(entity::class);
        $form = $crudmgmt->instanciate_related_form();
        $this->assertNotNull($form);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Make sure we have a logged in user.
        $PAGE->set_url(new \moodle_url('/'));
        $formdisplay = $form->render();
        $this->assertStringContainsString('input', $formdisplay);
    }

    public function test_instanciate_related_persistent_list() {
        global $PAGE;
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

        $crudmgmt = base::create(entity::class);
        $persistentlist = $crudmgmt->instanciate_related_persistent_list();
        $this->assertNotNull($persistentlist);
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

    /**
     * @dataProvider action_data_provider
     */
    public function test_setup_page($action, $expected) {
        $page = new \moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['title'], $page->title);
    }

    /**
     * @dataProvider action_data_provider
     */
    public function test_page_header($action, $expected) {
        $page = new \moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['header'], $page->heading);
    }

    /**
     * @dataProvider action_data_provider
     */
    public function test_get_action_event_description($action, $expected) {
        $page = new \moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['eventdescription'], $page->title);
    }

    /**
     * @dataProvider action_data_provider
     */
    public function test_get_action_event_class($action, $expected, $formdata = null) {
        global $PAGE;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $existingentity = new entity(0, (object) [
            'shortname' => 'Entity Test',
            'idnumber' => 'ETEST',
            'description' => 'Description',
            'descriptionformat' => FORMAT_HTML,
            'sortorder' => 0
        ]);
        $existingentity->create();
        $crudmgmt = base::create(entity::class, $action);
        if (!empty($formdata)) {
            $entityid = 0;
            if ($action != crud_add::ACTION) {
                $entityid = $existingentity->get('id');
                $formdata = $formdata + ['id' => $existingentity->get('id')];
            }
            $form = $crudmgmt->instanciate_related_form($entityid);
            forward_static_call([get_class($form), 'mock_submit'], [$formdata]);
        }
        $crudmgmt->setup_page($PAGE);
        $crudmgmt->action_process();

    }

    /**
     * @return array[]
     */
    public function action_data_provider() {
        return [
            'add' => [
                'action' => crud_add::ACTION,
                'expected' => [
                    'title' => 'Add Simple entity',
                    'header' => 'Add Simple entity',
                    'eventdescription' => 'Add Simple entity',
                    'eventclass' => ''
                ],
                'formdata' => [
                    'shortname' => 'Entity Test Added',
                    'idnumber' => 'ETEST1',
                    'description' => 'Description',
                    'descriptionformat' => FORMAT_HTML,
                ],
            ],
            'delete' => [
                'action' => crud_delete::ACTION,
                'expected' => [
                    'title' => 'Delete Simple entity',
                    'header' => 'Delete Simple entity',
                    'eventdescription' => 'Delete Simple entity',
                    'eventclass' => ''
                ],
            ],
            'edit' => [
                'action' => crud_edit::ACTION,
                'expected' => [
                    'title' => 'Edit Simple entity',
                    'header' => 'Edit Simple entity',
                    'eventdescription' => 'Edit Simple entity',
                    'eventclass' => ''
                ],
                'formdata' => [
                    'shortname' => 'Entity Test Modified',
                    'idnumber' => 'ETEST1',
                    'description' => 'Description',
                    'descriptionformat' => FORMAT_HTML,
                ]
            ],
            'list' => [
                'action' => crud_list::ACTION,
                'expected' => [
                    'title' => 'List Simple entity',
                    'header' => 'List Simple entity',
                    'eventdescription' => 'List Simple entity',
                    'eventclass' => ''
                ]
            ]
        ];
    }

    protected function create_fake_form_payload() {

    }
}
