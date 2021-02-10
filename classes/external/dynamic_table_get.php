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
 * Dynamic table get
 *
 * This is basically for Moodle 3.9 the same as 'extends \table_sql implements dynamic_table'
 * but with the capability to find the core table in persistent namespace
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_cltools\external;

use core_table\external\dynamic\get;

class dynamic_table_get extends get {
    /**
     * External function to get the table view content.
     *
     * @param string $component The component.
     * @param string $handler Dynamic table class name.
     * @param string $uniqueid Unique ID for the container.
     * @param array $sortdata The columns and order to sort by
     * @param array $filters The filters that will be applied in the request.
     * @param string $jointype The join type.
     * @param string $firstinitial The first name initial to filter on
     * @param string $lastinitial The last name initial to filter on
     * @param int $pagenumber The page number.
     * @param int $pagesize The number of records.
     * @param string $jointype The join type.
     * @param bool $resetpreferences Whether it is resetting table preferences or not.
     *
     * @return array
     */
    public static function execute(
        string $component,
        string $handler,
        string $uniqueid,
        array $sortdata,
        ?array $filters = null,
        ?string $jointype = null,
        ?string $firstinitial = null,
        ?string $lastinitial = null,
        ?int $pagenumber = null,
        ?int $pagesize = null,
        ?array $hiddencolumns = null,
        ?bool $resetpreferences = null
    ) {
        global $PAGE;

        [
            'component' => $component,
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'firstinitial' => $firstinitial,
            'lastinitial' => $lastinitial,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
            'resetpreferences' => $resetpreferences,
        ] = self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'handler' => $handler,
            'uniqueid' => $uniqueid,
            'sortdata' => $sortdata,
            'filters' => $filters,
            'jointype' => $jointype,
            'firstinitial' => $firstinitial,
            'lastinitial' => $lastinitial,
            'pagenumber' => $pagenumber,
            'pagesize' => $pagesize,
            'hiddencolumns' => $hiddencolumns,
            'resetpreferences' => $resetpreferences,
        ]);

        $tableclass = "\\{$component}\\table\\{$handler}";
        if (!class_exists($tableclass)) {
            $tableclass = "\\{$component}\\local\\persistent\\{$handler}\\entities_list";

            if (!class_exists($tableclass)) {
                throw new \UnexpectedValueException("Table handler class {$tableclass} not found. " .
                    "Please make sure that your table handler class is under the \\{$component}\\table or 
                    \\{$component}\\local\\persistent\\{$handler}\\entities_list  namespace.");
            }

        }

        if (!is_subclass_of($tableclass, \core_table\dynamic::class)) {
            throw new \UnexpectedValueException("Table handler class {$tableclass} does not support dynamic updating.");
        }

        $filtersetclass = "{$tableclass}_filterset";
        if (!class_exists($filtersetclass)) {
            throw new \UnexpectedValueException("The filter specified ({$filtersetclass}) is invalid.");
        }

        $filterset = new $filtersetclass();
        $filterset->set_join_type($jointype);
        foreach ($filters as $rawfilter) {
            $filterset->add_filter_from_params(
                $rawfilter['name'],
                $rawfilter['jointype'],
                $rawfilter['values']
            );
        }

        $instance = new $tableclass($uniqueid);
        $instance->set_filterset($filterset);
        self::validate_context($instance->get_context());

        $instance->set_sortdata($sortdata);
        $alphabet = get_string('alphabet', 'langconfig');

        if ($firstinitial !== null && ($firstinitial === '' || strpos($alphabet, $firstinitial) !== false)) {
            $instance->set_first_initial($firstinitial);
        }

        if ($lastinitial !== null && ($lastinitial === '' || strpos($alphabet, $lastinitial) !== false)) {
            $instance->set_last_initial($lastinitial);
        }

        if ($pagenumber !== null) {
            $instance->set_page_number($pagenumber);
        }

        if ($pagesize === null) {
            $pagesize = 20;
        }

        if ($hiddencolumns !== null) {
            $instance->set_hidden_columns($hiddencolumns);
        }

        if ($resetpreferences === true) {
            $instance->mark_table_to_reset();
        }

        $PAGE->set_url($instance->baseurl);

        ob_start();
        $instance->out($pagesize, true);
        $tablehtml = ob_get_contents();
        ob_end_clean();

        return [
            'html' => $tablehtml,
            'warnings' => []
        ];
    }

}