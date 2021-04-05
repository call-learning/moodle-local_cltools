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
 * Class for rendering user filters on the course participants page.
 *
 * @package    core_user
 * @copyright  2020 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\output;

use context_course;
use renderable;
use renderer_base;
use stdClass;
use templatable;
defined('MOODLE_INTERNAL') || die;
/**
 * Class for rendering user filters on the course participants page.
 *
 * @copyright  2020 Michael Hawkins <michaelh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_filter extends \core_user\output\participants_filter {
    /**
     * Participants filter constructor.
     *
     * @param context_course $context The context where the filters are being rendered.
     * @param string $tableregionid The table to be updated by this filter
     */
    public function __construct(string $tableregionid) {
        parent::__construct(context_course::instance(SITEID), $tableregionid);
    }

    /**
     * Get data for all filter types.
     *
     * @return array
     */
    protected function get_filtertypes(): array {
        $filtertypes = [];

        $filtertypes[] = $this->get_keyword_filter();
        if ($filtertype = $this->get_roles_filter()) {
            $filtertypes[] = $filtertype;
        }
        if ($filtertype = $this->get_accesssince_filter()) {
            $filtertypes[] = $filtertype;
        }

        return $filtertypes;
    }
}
