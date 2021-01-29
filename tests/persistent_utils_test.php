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

namespace local_cltools;
// https://docs.moodle.org/dev/Coding_style#Namespaces_within_.2A.2A.2Ftests_directories.
require_once(__DIR__ . '/fixtures/simple/entity.php');

use context_user;
use local_cltools\local\crud\persistent_utils;
use local_cltools\simple\entity;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Persistent utils test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cltools_persistent_utils_testcase extends \advanced_testcase {
    /**
     * Setup persistent table
     */
    public function setUp() {
        entity::create_table(true);
        $this->resetAfterTest();
    }

    public function tearDown() {
        entity::delete_table();
        parent::tearDown();
    }

    public function test_get_persistent_prefix() {
        $persistentprefix = persistent_utils::get_persistent_prefix("\\local_cltools\\simple\\entity");
        $this->assertEquals('simple', $persistentprefix);
    }

    public function test_is_reserved_property() {
        $this->assertTrue(persistent_utils::is_reserved_property('timecreated'));
        $this->assertTrue(persistent_utils::is_reserved_property('timemodified'));
        $this->assertTrue(persistent_utils::is_reserved_property('usermodified'));
        $this->assertTrue(persistent_utils::is_reserved_property('id'));
        $this->assertFalse(persistent_utils::is_reserved_property('name'));
    }

    public function test_external_get_filter_generic_parameters() {
        $this->assertnotNull(static::external_get_filter_generic_parameters());
    }

    public function test_external_get_files_url() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->upload_files(array('icon.png'), array('icon'), 'simple', 'local_cltools');
        $files = persistent_utils::get_files_urls(0, 'simple', 'local_cltools');
        $this->assertCount(1, $files);
        $this->assertEquals('https://www.example.com/moodle/pluginfile.php/1/local_cltools/simple/0/icon',
            $files[0]->out());
        $this->delete_files('testfilearea', 'local_cltools');
    }

    public function test_external_get_files_url_with_item_id() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->upload_files(array('icon.png', 'icon.png'), array('icon', 'icon2'), 'simple', 'local_cltools', 1);
        $files = persistent_utils::get_files_urls(0, 'simple', 'local_cltools');
        $this->assertCount(0, $files);
        $files = persistent_utils::get_files_urls(1, 'simple', 'local_cltools');
        $this->assertCount(2, $files);
        $this->assertEquals('https://www.example.com/moodle/pluginfile.php/1/local_cltools/simple/1/icon',
            $files[0]->out());
        $this->assertEquals('https://www.example.com/moodle/pluginfile.php/1/local_cltools/simple/1/icon2',
            $files[1]->out());
        $this->delete_files('testfilearea', 'local_cltools');
    }


    /**
     * Upload a set of files in a given area
     *
     * @param array $imagefilenames
     * @param array $destfilenames
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function upload_files($imagefilenames, $destfilenames, $filearea, $component, $itemid = 0) {
        $contextsystem = \context_system::instance();
        $draftitemid = file_get_unused_draft_itemid();
        global $CFG;
        $filesid = [];
        $fs = get_file_storage();
        foreach ($imagefilenames as $index => $filename) {
            $destfilename = $filename;
            if (!empty($destfilenames[$index])) {
                $destfilename = $destfilenames[$index];
            }
            $filerecord = array(
                'contextid' => $contextsystem->id,
                'component' => $component,
                'filearea' => $filearea,
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => $destfilename,
            );
            // Create an area to upload the file.
            // Create a file from the string that we made earlier.
            if (!($file = $fs->get_file($filerecord['contextid'],
                $filerecord['component'],
                $filerecord['filearea'],
                $filerecord['itemid'],
                $filerecord['filepath'],
                $filerecord['filename']))) {
                $filerecord['itemid'] = $itemid;
                $file = $fs->create_file_from_pathname($filerecord,
                    $CFG->dirroot . '/local/cltools/tests/fixtures/files/' . $filename);
            }
            $filesid[] = $file->get_id();
        }
        return $filesid;

    }

    protected function delete_files($filearea, $component) {
        $contextsystem = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($contextsystem->id, $component, $filearea);
    }
}
