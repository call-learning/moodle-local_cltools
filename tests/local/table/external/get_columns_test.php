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

namespace local_cltools\local\table\external;

use advanced_testcase;
use local_cltools\simple\entity;
use local_cltools\simple\table;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');

require_once($CFG->dirroot . '/local/cltools/tests/lib.php');

/**
 * API test
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_columns_test extends advanced_testcase {
    /**
     * Setup
     */
    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        entity::delete_table();
        entity::create_table();
    }

    /**
     * Test an API function
     */
    public function test_get_table_columns_not_logged_in() {
        $this->expectException('require_login_exception');
        get_columns::execute(table::class, "", '');
    }

    /**
     * Test an API function
     */
    public function test_get_table_columns() {
        $this->setAdminUser();
        $columns = get_columns::execute(table::class, "", '');
        $this->assertNotEmpty($columns);
        $expected = json_decode(self::EXPECTED_COLUMNS_JSON);
        $this->assertEquals($expected, $columns);
    }

    const EXPECTED_COLUMNS_JSON = '[
    {
        "title": "shortname",
        "field": "shortname",
        "visible": true,
        "headerFilter": "input",
        "headerFilterParams": ""
    },
    {
        "title": "idnumber",
        "field": "idnumber",
        "visible": true,
        "headerFilter": "input",
        "headerFilterParams": ""
    },
    {
        "title": "description",
        "field": "description",
        "visible": false
    },
    {
        "title": "parentid",
        "field": "parentid",
        "visible": true,
        "headerSort": true,
        "formatter": "entity_lookup",
        "formatterParams": "{\"entityclass\":\"local_cltools\\\\\\\\simple\\\\\\\\entity\",\"displayfield\":\"\"}",
        "headerFilter": "entity_lookup",
        "headerFilterParams": "{\"entityclass\":\"local_cltools\\\\\\\\simple\\\\\\\\entity\",\"displayfield\":\"\"}"
    },
    {
        "title": "path",
        "field": "path",
        "visible": true,
        "headerFilter": "input",
        "headerFilterParams": ""
    },
    {
        "title": "sortorder",
        "field": "sortorder",
        "visible": true,
        "headerSort": true,
        "formatter": "number",
        "headerFilter": "number"
    },
    {
        "title": "othersimpleid",
        "field": "othersimpleid",
        "visible": true,
        "headerSort": true,
        "formatter": "entity_lookup",
        "formatterParams": "{\"entityclass\":\"local_cltools\\\\\\\\othersimple\\\\\\\\entity\",\"displayfield\":\"\"}",
        "headerFilter": "entity_lookup",
        "headerFilterParams": "{\"entityclass\":\"local_cltools\\\\\\\\othersimple\\\\\\\\entity\",\"displayfield\":\"\"}"
    },
    {
        "title": "scaleid",
        "field": "scaleid",
        "visible": true,
        "headerSort": true,
        "formatter": "lookup",
        "formatterParams": "{\"1\":\"scale1\",\"2\":\"scale2\"}",
        "headerFilter": "select",
        "headerFilterParams": "{\"values\":{\"1\":\"scale1\",\"2\":\"scale2\"}}"
    },
    {
        "title": "image",
        "field": "image",
        "visible": true,
        "headerSort": true
    },
    {
        "title": "Actions",
        "field": "actions",
        "visible": true,
        "headerSort": false,
        "formatter": "html"
    },
    {
        "title": "id",
        "field": "id",
        "visible": false
    }
]';
}
