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
 * Rotation entity edit or add form
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\simple;

use local_cltools\local\crud\form\entity_form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Add Form
 *
 * @package     local_cltools
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends entity_form {
    /**
     * Form property in order to display the right widget for the form.
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    protected static function define_properties() {
        global $DB;
        $scales = $DB->get_records_menu('scale', null, '', 'id,name');
        return array(
            'shortname' => (object) [
                'type' => 'text'
            ],
            'idnumber' => [
                'type' => 'text'
            ],
            'description' => [
                'type' => 'editor'
            ],
            'parentid' => [
                'type' => 'int'
            ],
            'path' => [
                'type' => 'text'
            ],
            'sortorder' => array(
                'type' => 'int'
            ),
            'scaleid' => array(
                'type' => 'int'
            ),
            'files' => [
                'type' => 'file_manager'
            ]
        );

    }

    /** @var string The fully qualified classname. */
    protected static $persistentclass = '\\local_cltools\\local\\simple\\entity';

    /** @var array Fields to remove when getting the final data. */
    protected static $fieldstoremove = array('submitbutton', 'files');

    /** @var string[] $foreignfields */
    protected static $foreignfields = array();
}