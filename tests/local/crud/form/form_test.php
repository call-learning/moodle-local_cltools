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

use coding_exception;
use local_cltools\simple\entity;
use local_cltools\simple\form;
use local_cltools\test\base_crud_test;
use moodle_url;

/**
 * Persistent form test case
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_test extends base_crud_test {
    /**
     * A smoke check to see if all fields are represented
     *
     * @return void
     * @throws coding_exception
     */
    public function test_simple_form_has_all_entity_fields() {
        global $PAGE;
        $this->setAdminUser();
        $PAGE->set_url(new moodle_url('/'));
        $form = new form();

        $renderableform = $form->render();
        foreach (entity::define_fields() as $field) {
            $this->assertContains('id_' . $field->get_name(), $renderableform);
        }
    }
}

