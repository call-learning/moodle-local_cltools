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
namespace local_cltools\local\crud\navigation;

use core\form\persistent;
use local_cltools\local\crud\helper\crud_add;
use local_cltools\local\crud\helper\crud_delete;
use local_cltools\local\crud\helper\crud_edit;
use local_cltools\local\crud\helper\crud_view;
use moodle_url;

/**
 * Class routed_navigation: routed navigation
 *
 *
 * Implements a navigation with one file/page per action.
 *
 * @package local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class routed_navigation extends flat_navigation {
    /**
     * @var persistent|null $persistentclass current persistent class
     */
    protected $persistentclass = null;
    /**
     * @var moodle_url $rooturl Root URL
     */
    protected $rooturl = null;

    /**
     * Get URL for a list of entities
     *
     * @return moodle_url
     */
    public function get_list_url(): moodle_url {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php");
    }

    /**
     * Get the URL for adding a new entity
     *
     * @return moodle_url
     */
    public function get_add_url(): moodle_url {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", ['action' => crud_add::ACTION]);
    }

    /**
     * Get the URL for deleting an entity
     *
     * @return moodle_url
     */
    public function get_delete_url(): moodle_url {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", ['action' => crud_delete::ACTION]);
    }

    /**
     * Get the URL for editing an entity
     *
     * @return moodle_url
     */
    public function get_edit_url(): moodle_url {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", ['action' => crud_edit::ACTION]);
    }

    /**
     * Get the URL for viewing an entity
     *
     * @return moodle_url
     */
    public function get_view_url(): moodle_url {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php", ['action' => crud_view::ACTION]);
    }
}
