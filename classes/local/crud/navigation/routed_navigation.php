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
 * Flat navigation class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\navigation;

use local_cltools\local\crud\helper\crud_add;
use local_cltools\local\crud\helper\crud_delete;
use local_cltools\local\crud\helper\crud_edit;
use local_cltools\local\crud\helper\crud_view;
use moodle_url;
use ReflectionException;

/**
 * Class flat_navigation
 *
 *
 * Implements a navigation with one file/page per action.
 *
 * @package local_cltools\local\crud\navigation
 */
class routed_navigation extends flat_navigation {
    protected $persistentclass = null;
    protected $rooturl = null;

    /**
     * @return moodle_url
     * @throws ReflectionException
     */
    public function get_list_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php");
    }

    /**
     * @return moodle_url
     * @throws ReflectionException
     */
    public function get_add_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", array('action' => crud_add::ACTION));
    }

    /**
     * @return moodle_url
     * @throws ReflectionException
     */
    public function get_delete_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", array('action' => crud_delete::ACTION));
    }

    public function get_edit_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", array('action' => crud_edit::ACTION));
    }

    /**
     * @return moodle_url
     * @throws ReflectionException
     */
    public function get_view_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", array('action' => crud_view::ACTION));
    }
}
