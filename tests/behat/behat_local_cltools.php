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

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

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
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * A typical example might be:
     *     When I am on the "Test quiz" "mod_quiz > Responses report" page
     * which would cause this method in behat_mod_quiz to be called with
     * arguments 'Responses report', 'Test quiz'.
     *
     * You should override this as appropriate for your plugin. The method
     * {@link behat_navigation::resolve_core_page_instance_url()} is a good example.
     *
     * Your overridden method should document the recognised page types with
     * a table like this:
     *
     * Recognised page names are:
     * | Type      | identifier meaning | Description                                     |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test quiz > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $CFG;
        require_once($CFG->dirroot.'/local/cltools/tests/lib.php');
        switch($type) {
            case 'Entity edit':
                $parts = explode('>', $identifier);
                $entitytype = trim($parts[0]);
                $entityid = trim($parts[1]);
                $entity = $entitytype::get_record(['idnumber' => $entityid]);
                return new moodle_url("/local/cltools/tests/fixtures/pages/{$identifier}/edit.php", ['id' => $entity->get('id')]);
            case 'Entity add':
                return new moodle_url("/local/cltools/tests/fixtures/pages/{$identifier}/add.php");
            case 'Entity list':
                return new moodle_url("/local/cltools/tests/fixtures/pages/{$identifier}/list.php");
        }
        throw new Exception('Unrecognised page type "' . $type . '."');
    }

    /**
     * Create the simple entity / other entities tables for testing
     *
     * @BeforeScenario
     */
    public function before_scenario(BeforeScenarioScope $scope) {
        global $CFG;
        require_once($CFG->dirroot.'/local/cltools/tests/lib.php');
        \local_cltools\othersimple\entity::delete_table();
        \local_cltools\simple\entity::delete_table();
        \local_cltools\othersimple\entity::create_table();
        \local_cltools\simple\entity::create_table();
    }
}
