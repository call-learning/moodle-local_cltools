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
namespace local_cltools\local\crud\helper;

use local_cltools\simple\entity;
use local_cltools\test\base_crud_test;
use moodle_page;
use moodle_url;

/**
 * Persistent utils test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_cltools\local\crud\helper\base;
 */
class base_test extends base_crud_test {
    /**
     * Get crud management
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_get_crud_default() {
        $crudmgmt = base::create(entity::class);
        // Add by default.
        $this->assertNotNull($crudmgmt);
        $this->assertEquals('local_cltools\local\crud\helper\crud_add', get_class($crudmgmt));
    }

    /**
     * Test that we can instanciate the form
     *
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_instanciate_related_form() {
        global $PAGE;
        $crudmgmt = base::create(entity::class);
        $form = $crudmgmt->instanciate_related_form();
        $this->assertNotNull($form);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Make sure we have a logged in user.
        $PAGE->set_url(new moodle_url('/'));
        $formdisplay = $form->render();
        $this->assertStringContainsString('input', $formdisplay);
    }
    /**
     * Test that we can instanciate the table
     *
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_instanciate_related_persistent_table() {
        global $PAGE;
        // Create a couple of entities.
        $entitiesdata = [
                (object) [
                        'shortname' => 'Shortname 1',
                        'idnumber' => 'Idnumber 1',
                        'description' => 'Description',
                        'descriptionformat' => FORMAT_HTML,
                        'sortorder' => 0,
                        'othersimpleid' => 0,
                        'scaleid' => 0,
                        'parentid' => 0
                ],
                (object) [
                        'shortname' => 'Shortname 2',
                        'idnumber' => 'Idnumber 2',
                        'description' => 'Description',
                        'descriptionformat' => FORMAT_HTML,
                        'sortorder' => 0,
                        'othersimpleid' => 0,
                        'scaleid' => 0,
                        'parentid' => 0
                ]
        ];
        foreach ($entitiesdata as $entityrecord) {
            $entity = new entity(0, $entityrecord);
            $entity->save();
        }

        $crudmgmt = base::create(entity::class);
        $persistenttable = $crudmgmt->instanciate_related_persistent_table();
        $this->assertNotNull($persistenttable);
        $PAGE->set_url(new moodle_url('/'));
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user); // Make sure we have a logged in user.
        $rows = $persistenttable->get_rows(1000, true);
        $this->assertStringContainsString('Shortname 1', $rows[0]->shortname);
        $this->assertStringContainsString('Shortname 2', $rows[1]->shortname);
        $this->assertNotEmpty($rows[0]->actions); // This should be actually included in the table.
    }

    /**
     * Test page setup
     *
     * @param string $action
     * @param array $expected
     * @dataProvider action_data_provider
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_setup_page(string $action, array $expected) {
        $page = new moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['title'], $page->title);
    }

    /**
     * Test page header generation
     *
     * @param string $action
     * @param array $expected
     * @dataProvider action_data_provider
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_page_header(string $action, array $expected) {
        $page = new moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['header'], $page->heading);
    }

    /**
     * Test description
     *
     * @param string $action
     * @param array $expected
     * @dataProvider action_data_provider
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_get_action_event_description(string $action, array $expected) {
        $page = new moodle_page();
        $crudmgmt = base::create(entity::class, $action);
        $crudmgmt->setup_page($page);
        $this->assertEquals($expected['eventdescription'], $page->title);
    }

    /**
     * Test action event class
     *
     * @param string $action
     * @param array $expected
     * @param array|null $formdata
     * @dataProvider action_data_provider
     * @covers \local_cltools\local\crud\helper\base
     */
    public function test_get_action_event_class(string $action, array $expected, $formdata = null) {
        global $PAGE;
        $user = $this->getDataGenerator()->create_user();
        $eventsink = $this->redirectEvents();
        $this->setUser($user);
        $existingentity = new entity(0, (object) [
                'shortname' => 'Entity Test',
                'idnumber' => 'ETEST',
                'description' => 'Description',
                'descriptionformat' => FORMAT_HTML,
                'sortorder' => 0,
                'othersimpleid' => 0,
                'scaleid' => 0,
                'parentid' => 0
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
            forward_static_call([get_class($form), 'mock_submit'], $formdata);
        } else {
            if ($action == crud_delete::ACTION) {
                $simulatedsubmitteddata = [];
                $simulatedsubmitteddata['id'] = $existingentity->get('id');
                $simulatedsubmitteddata['confirm'] = true;
                $simulatedsubmitteddata['sesskey'] = sesskey();
                $_POST = $simulatedsubmitteddata;
            }
        }
        $crudmgmt->setup_page($PAGE);
        $crudmgmt->action_process();
        if (empty($expected['eventclass'])) {
            $this->assertEmpty($eventsink->get_events());
        } else {
            $this->assertNotEmpty($eventsink->get_events());
            $event = $eventsink->get_events()[0];
            $this->assertEquals($expected['eventclass'], get_class($event));
        }
    }

    /**
     * Get data provider
     *
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
                                'eventclass' => 'local_cltools\\event\\simple_added'
                        ],
                        'formdata' => [
                                'shortname' => 'Entity Test Added',
                                'idnumber' => 'ETEST1',
                                'description' => 'Description',
                                'descriptionformat' => FORMAT_HTML,
                                'parentid' => "",
                                'otherentityid' => "",
                                'scaleid' => ""
                        ],
                ],
                'delete' => [
                        'action' => crud_delete::ACTION,
                        'expected' => [
                                'title' => 'Delete Simple entity',
                                'header' => 'Delete Simple entity',
                                'eventdescription' => 'Delete Simple entity',
                                'eventclass' => 'local_cltools\\event\\crud_event_deleted'
                        ],
                ],
                'edit' => [
                        'action' => crud_edit::ACTION,
                        'expected' => [
                                'title' => 'Edit Simple entity',
                                'header' => 'Edit Simple entity',
                                'eventdescription' => 'Edit Simple entity',
                                'eventclass' => 'local_cltools\\event\\simple_edited'
                        ],
                        'formdata' => [
                                'shortname' => 'Entity Test Modified',
                                'idnumber' => 'ETEST1',
                                'description' => 'Description',
                                'descriptionformat' => FORMAT_HTML,
                                'parentid' => "",
                                'otherentityid' => "",
                                'scaleid' => ""
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
}
