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
 * CL Tools additional steps
 *
 * @package    local_cltools
 * @copyright  2021 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions
 *
 * @package    local_cltools
 * @category   test
 * @copyright  2021 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_cltools extends behat_base {

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * You should override this as appropriate for your plugin. The method
     * {@link behat_navigation::resolve_core_page_url()} is a good example.
     *
     * Your overridden method should document the recognised page types with
     * a table like this:
     *
     * Recognised page names are:
     * | Page            | Description                                                    |
     * | Simple Entity   | Simple entity add
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        return new moodle_url('/local/cltools/crudpages/simple/add.php');
    }

    /**
     * Create the simple entity / other entities tables for testing
     *
     * @BeforeSuite
     */
    public static function setup_simple_entity_table(SuiteEvent $event) {
        \local_cltools\local\simple\entity::create_table();
    }

    /**
     * Delete the simple entity / other entities tables for testing
     *
     * @AfterSuite
     */
    public static function drop_simple_entity_table(SuiteEvent $event) {
        \local_cltools\local\simple\entity::delete_table();
    }

}
