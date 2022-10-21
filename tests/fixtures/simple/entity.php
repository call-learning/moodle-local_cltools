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
 * Simple entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\simple;

use core\persistent;
use ddl_exception;
use ddl_table_missing_exception;
use local_cltools\local\crud\enhanced_persistent;
use local_cltools\local\crud\enhanced_persistent_impl;
use local_cltools\local\field\editor;
use local_cltools\local\field\entity_selector;
use local_cltools\local\field\files;
use local_cltools\local\field\number;
use local_cltools\local\field\persistent_field;
use local_cltools\local\field\select_choice;
use local_cltools\local\field\text;
use stdClass;
use xmldb_table;

/**
 * Class Sample entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity extends persistent implements enhanced_persistent {

    use enhanced_persistent_impl;

    /**
     * @var $string TABLE
     */
    const TABLE = 'simple';


    /**
     * Create an instance of this class. Make sure that non persistent fields are removed before
     * calling from record.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param stdClass|null $record If set will be passed to {@see self::from_record()}.
     */
    public function __construct($id = 0, stdClass $record = null) {
        if (!empty($record)) {
            // Make sure we remove non persitent fields before we build the entity.
            $fields = self::define_fields();
            foreach ($fields as $f) {
                /* @var persistent_field $f a persistent field */
                if (!$f->is_persistent()) {
                    $fname = $f->get_name();
                    if (isset($record->$fname)) {
                        unset($record->$fname);
                    }
                }
            }
        }
        parent::__construct($id, $record);
    }

    /**
     * Usual properties definition for a persistent
     *
     * @return array|array[]
     */
    public static function define_fields(): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/cltools/tests/fixtures/othersimple/entity.php');
        return array(
                new text('shortname'),
                new text('idnumber'),
                new editor('description'), // Description format is automatically added.
                new entity_selector([
                        'fieldname' => 'parentid',
                        'entityclass' => self::class,
                        'displayfield' => 'shortname'
                ]),
                new text('path'),
                new number('sortorder'),
                new entity_selector([
                        'fieldname' => 'othersimpleid',
                        'entityclass' => \local_cltools\othersimple\entity::class,
                        'displayfield' => 'shortname'
                ]),
                new select_choice([
                        'fieldname' => 'scaleid',
                        'choices' => [1 => 'scale1', 2 => 'scale2']
                ]),
                new files('image'),
        );
    }


    /**
     * This is specific to the test environment. We create the table structure.
     */

    /**
     * This is specific to the test environment. We create the table structure.
     *
     * If the table exist we leave it as it is.
     *
     * @throws ddl_exception
     * @throws ddl_table_missing_exception
     */
    public static function create_table() {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table(static::TABLE);

        if ($dbman->table_exists($table)) {
            return;
        }
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('othersimpleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
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
