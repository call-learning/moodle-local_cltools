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
 * Persistent form test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\form;
// See https://docs.moodle.org/dev/Coding_style#Namespaces_within_.2A.2A.2Ftests_directories.
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/cltools/tests/lib.php');

use local_cltools\local\crud\generic\generic_entity_form_generator;
use local_cltools\local\field\files;
use local_cltools\local\field\editor;
use local_cltools\simple\entity;
use local_cltools\simple\form;
use local_cltools\test\base_crud_test_helper;
use moodle_url;

/**
 * Persistent form test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_test extends base_crud_test_helper {
    /**
     * A smoke check to see if all fields are represented
     *
     * @covers \local_cltools\local\crud\form\entity_form
     */
    public function test_simple_form_has_all_entity_fields() {
        global $PAGE;
        $this->setAdminUser();
        $PAGE->set_url(new moodle_url('/'));
        $form = new form();

        $renderableform = $form->render();
        foreach (entity::define_fields() as $field) {
            $this->assertStringContainsString('id_' . $field->get_name(), $renderableform);
        }
    }

    /**
     * Check for files that should be saved
     *
     * @covers \local_cltools\local\crud\form\entity_form
     */
    public function test_form_submit_image() {
        global $CFG, $OUTPUT;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        // Create an entity.
        $submitteddata = [
                'shortname' => 'Shortname 1',
                'idnumber' => 'Idnumber 1',
                'description_editor' => [
                        'text' => 'Description',
                        'format' => FORMAT_HTML,
                        'itemid' => null,
                ],
                'sortorder' => 0,
                'othersimpleid' => 0,
                'scaleid' => 0,
                'parentid' => 0,
        ];

        ['draftitemid' => $submitteddata['image']] = $this->create_user_file_in_draft_area($user,
                [$CFG->dirroot . '/local/cltools/tests/fixtures/tux-sample.png']);
        generic_entity_form::mock_submit($submitteddata);
        $form = generic_entity_form_generator::generate(entity::class, []);
        $form->set_data($submitteddata);
        $form->prepare_for_files();
        $entity = $form->save_data();
        $field = new files('image');
        $value = $field->format_value($entity, $OUTPUT);
        $this->assertStringContainsString('tux-sample.png', $value);
        $this->assertStringContainsString('/local_cltools/simple_image/', $value);
    }

    /**
     * Create a user file in draft area so to
     *
     * @param object $user
     * @param array $filepaths
     * @param string|null $text
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    private function create_user_file_in_draft_area($user, $filepaths, $text = null) {
        // Create user private files.
        $fs = get_file_storage();
        foreach ($filepaths as $fp) {
            $userfilerecord = new \stdClass;
            $userfilerecord->contextid = \context_user::instance($user->id)->id;
            $userfilerecord->component = 'user';
            $userfilerecord->filearea = 'private';
            $userfilerecord->itemid = 0;
            $userfilerecord->filepath = '/';
            $userfilerecord->filename = basename($fp);
            $userfilerecord->source = 'test';
            $userfile = $fs->create_file_from_pathname($userfilerecord, $fp);
        }

        $draftitemid = 0;
        $text = file_prepare_draft_area($draftitemid,
                $userfile->get_contextid(),
                $userfile->get_component(),
                $userfile->get_filearea(),
                $userfile->get_itemid(),
                null,
                $text);
        return ['draftitemid' => $draftitemid, 'text' => $text];
    }

    /**
     * Check for files that should be saved
     *
     * @covers \local_cltools\local\crud\form\entity_form
     */
    public function test_form_submit_editor_content() {
        global $CFG, $OUTPUT;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        // Create an entity.
        $submitteddata = [
                'shortname' => 'Shortname 1',
                'idnumber' => 'Idnumber 1',
                'description_editor' => [
                        'text' => 'Description my text <image src="@@PLUGINFILE@@/tux-sample.png"/>',
                        'format' => FORMAT_HTML,
                        'itemid' => null,
                ],
                'sortorder' => 0,
                'othersimpleid' => 0,
                'scaleid' => 0,
                'parentid' => 0,
        ];

        [
                'draftitemid' => $submitteddata['description_editor']['itemid'],
                'text' => $submitteddata['description_editor']['text']
        ] = $this->create_user_file_in_draft_area($user,
                [$CFG->dirroot . '/local/cltools/tests/fixtures/tux-sample.png'],
                $submitteddata['description_editor']['text']);
        generic_entity_form::mock_submit($submitteddata);
        $form = generic_entity_form_generator::generate(entity::class, []);
        $form->set_data($submitteddata);
        $form->prepare_for_files();
        $entity = $form->save_data();
        $field = new editor('description');
        $value = $field->format_value($entity, $OUTPUT);
        $this->assertStringContainsString('tux-sample.png', $value);
        $this->assertStringContainsString('/local_cltools/simple_description/', $value);
        $this->assertStringContainsString('Description my text ', $value);

    }
}

