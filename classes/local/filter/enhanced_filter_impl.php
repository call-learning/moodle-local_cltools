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
 * Enhanced filter
 *
 * @package    local_cltools
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @copyright  2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\local\filter;

/**
 * Enhanced filter
 *
 * @package    local_cltools
 * @copyright  2020 Andrew Nicols <andrew@nicols.co.uk>
 * @copyright  2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait enhanced_filter_impl {
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseFunction
    /**
     * Serialize filter.
     *
     * @return object
     */
    public function jsonSerialize(): object {
        return (object) [
                'type' => $this->get_type(),
                'name' => $this->get_name(),
                'jointype' => $this->get_join_type(),
                'values' => array_map(
                        function($val) {
                            return json_encode($val);
                        },
                        $this->get_filter_values()
                ),
        ];
    }

    /**
     * Get an identifier for this type of filter
     *
     * @return string
     */
    public function get_type(): string {
        $tableclass = explode("\\", get_class($this));
        return end($tableclass);
    }
}
