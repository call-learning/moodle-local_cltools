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
 * Interface persistent_navigation: Persistent navigation interface
 *
 * @package local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\local\crud\navigation;

use moodle_url;

/**
 * Interface persistent_navigation: Persistent navigation interface
 *
 * @package local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface  persistent_navigation {
    /**
     * Get URL for a list of entities
     *
     * @return moodle_url
     */
    public function get_list_url(): moodle_url;

    /**
     * Get the URL for adding a new entity
     *
     * @return moodle_url
     */
    public function get_add_url(): moodle_url;

    /**
     * Get the URL for deleting an entity
     *
     * @return moodle_url
     */
    public function get_delete_url(): moodle_url;

    /**
     * Get the URL for editing an entity
     *
     * @return moodle_url
     */
    public function get_edit_url(): moodle_url;

    /**
     * Get the URL for viewing an entity
     *
     * @return moodle_url
     */
    public function get_view_url(): moodle_url;
}
