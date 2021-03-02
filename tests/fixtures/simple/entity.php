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
 * Rotation entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\simple;

use lang_string;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Class rotation
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity extends \core\persistent {
    const TABLE = 'simple';

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    protected static function define_properties() {
        return array(
            'shortname' => array(
                'type' => PARAM_TEXT,
                'default' => ''
            ),
            'idnumber' => array(
                'type' => PARAM_TEXT,
            ),
            'description' => array(
                'type' => PARAM_RAW,
                'default' => '',
                'format' => [
                    'type' => 'editor'
                ]
            ),
            'descriptionformat' => array(
                'choices' => array(FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN),
                'type' => PARAM_INT,
                'default' => FORMAT_HTML
            ),
            'parentid' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'path' => array(
                'type' => PARAM_RAW,
                'default' => ''
            ),
            'sortorder' => array(
                'type' => PARAM_INT,
                'message' => new lang_string('invalidrequest', 'error')
            ),
            'scaleid' => array(
                'type' => PARAM_INT,
                'default' => null,
                'null' => NULL_ALLOWED
            )
        );
    }

    /**
     * This is specific to the test environment. We create the table structure.
     */

    /**
     * This is specific to the test environment. We create the table structure.
     *
     * @param false $dropexistingtable
     * @throws \ddl_exception
     * @throws \ddl_table_missing_exception
     */
    public static function create_table($dropexistingtable = false) {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table(static::TABLE);


        if ($dbman->table_exists($table)) {
            if ($dropexistingtable) {
                $dbman->drop_table($table);
            } else {
                return;
            }
        }
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
    }
    /**
     * This is specific to the test environment. We delete the table structure.
     */
    public static function delete_table() {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table(static::TABLE);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
    }
}
