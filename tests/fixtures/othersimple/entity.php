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

namespace local_cltools\othersimple;

use context;
use core\persistent;
use local_cltools\local\crud\enhanced_persistent;
use local_cltools\local\crud\enhanced_persistent_impl;
use local_cltools\local\field\number;
use local_cltools\local\field\text;
use restricted_context_exception;
use xmldb_table;

/**
 * Other simple entity
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity extends persistent implements enhanced_persistent {

    use enhanced_persistent_impl;

    /** @var string TABLE other simple */
    const TABLE = 'othersimple';

    /**
     * Usual properties definition for a persistent
     *
     * @return array
     */
    public static function define_fields(): array {
        return array(
                new text('shortname'),
                new text('idnumber'),
                new number('sortorder'),
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
     */
    public static function create_table(): void {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table(static::TABLE);

        if ($dbman->table_exists($table)) {
            return;
        }
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
    }

    /**
     * This is specific to the test environment. We delete the table structure.
     */
    public static function delete_table(): void {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table(static::TABLE);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
    }

    /**
     * Validate access
     *
     * @param context $context
     * @param bool $writeaccess
     * @return bool
     * @throws restricted_context_exception
     */
    public static function validate_access(context $context, $writeaccess = false): bool {
        return has_capability('local/cltools:dynamictableread', $context);
    }
}
