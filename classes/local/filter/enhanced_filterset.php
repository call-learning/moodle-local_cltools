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
 * Table filterset.
 *
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\filter;

use core_table\local\filter\filterset;
use local_cltools\local\filter\adapter\sql_adapter;

defined('MOODLE_INTERNAL') || die;

/**
 * Class representing a set of filters.
 *
 * @package   local_cltools
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enhanced_filterset extends filterset {
    /**
     * @var array
     */
    protected $optionalfilters = [];
    /**
     * @var array
     */
    protected $requiredfilters = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * Constructor
     *
     *
     * @param array $filtertypes We expect an associative array defining the field by their name
     * like ['field'=> ['filterclass'=> numeric_comparison_filter::class, 'optional'=>true]
     * If optional is not specified the field will be required automatically.
     */
    public function __construct(array $filtertypes) {
        foreach ($filtertypes as $fieldname => $fdef) {
            $fdef = is_array($fdef) ? (object) $fdef : $fdef;
            $this->add_filter_definition($fieldname, $fdef);
        }
    }

    /**
     * Add filter definition
     *
     * @param $fieldname
     * @param $filterdefinition
     */
    public function add_filter_definition($fieldname, $filterdefinition) {
        if (empty($filterdefinition->optional) || !empty($filterdefinition->required)) {
            $this->requiredfilters[$fieldname] = $filterdefinition->filterclass;
        } else {
            $this->optionalfilters[$fieldname] = $filterdefinition->filterclass;
        }
        if (!empty($filterdefinition->alias)) {
            $this->aliases[$fieldname] = $filterdefinition->alias;
        }
        // Hack: when adding a new filter we must refresh the filtertypes.
        $this->filtertypes = null;
        $this->get_all_filtertypes();
    }

    /**
     * Get the optional filters.
     *
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return $this->optionalfilters;
    }

    /**
     * Get the optional filters.
     *
     *
     * @return array
     */
    public function get_required_filters(): array {
        return $this->requiredfilters;
    }

    /**
     * Get the sql where / params used for filtering
     *
     * @param null $tableprefix
     * @param null $excludedfiltersname
     * @param array $forcedaliases
     * @return array
     */
    function get_sql_for_filter($tableprefix = null, $excludedfiltersname = null, $forcedaliases = []) {

        $joinsql = filter_helper::get_jointype_to_sql_join($this->get_join_type());
        $filtesetwheres = [];
        $filtesetparams = [];

        foreach ($this->get_filters() as $filter) {
            if (!empty($excludedfiltersname) && in_array($filter->get_name(), $excludedfiltersname)) {
                continue;
            }
            $interfaces = class_implements($filter);

            if (!empty($interfaces[sql_adapter::class])) {
                $alias = filter_helper::get_sanitized_name(
                        $filter->get_name()
                );
                if (!empty($this->aliases[$filter->get_name()])) {
                    $alias = $this->aliases[$filter->get_name()];
                }
            }
            if (!empty($tableprefix)) {
                $alias = "$tableprefix.$alias";
            }
            if (!empty($forcedaliases[$filter->get_name()])) {
                $alias = $forcedaliases[$filter->get_name()];
            }
            list($wheres, $params) = $filter->get_sql_filter($alias);
            if ($wheres) {
                $filtesetparams += $params;
                $filtesetwheres[] = $wheres;
            }
        }
        return array(join(" $joinsql ", $filtesetwheres), $filtesetparams);
    }
}
