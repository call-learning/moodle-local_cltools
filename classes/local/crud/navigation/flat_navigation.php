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

use local_cltools\local\crud\entity_utils;
use moodle_url;

/**
 * Class flat_navigation
 *
 *
 * Implements a navigation with one file/page per action.
 *
 * @package local_cltools\local\crud\navigation
 */
class flat_navigation implements persistent_navigation {
    protected $persistentclass = null;
    protected $rooturl = null;

    /**
     * flat_navigation constructor.
     *
     *
     * This will deduce the root url for all pages from the path of the class if rooturl not provided.
     *
     * @param $persistentclass
     * @param null $rooturl
     * @throws \ReflectionException
     */
    public function __construct($persistentclass, $rooturl = null) {
        $this->persistentclass = $persistentclass;
        if (!$rooturl) {
            global $CFG;
            // Deduce the path from the persistent class path.
            $rc = new \ReflectionClass($persistentclass);
            $filepath = dirname($rc->getFileName());
            $foldername = basename($filepath);
            if (strstr($filepath, $CFG->dirroot) === false) {
                throw new \moodle_exception('pagesdirectoryoutofrootdir', 'local_cltools', '', $filepath);
            }
            $filepath = str_replace($CFG->dirroot, '', $filepath);
            while ($filepath != '.' || empty($filepath)) {

                if (file_exists("{$CFG->dirroot}{$filepath}/pages/")) {
                    $rooturl = "{$filepath}/pages/$foldername";
                    break;
                }
                if (file_exists("{$CFG->dirroot}{$filepath}/pages/$foldername" )) {
                    $rooturl = "{$filepath}/pages/$foldername";
                    break;
                }
                $filepath = dirname($filepath);
            }
            if (empty($rooturl)) {
                throw new \moodle_exception('pagesdirectorydoesnotexist', 'local_cltools', '', $filepath);
            }
        }
        $this->set_root_url($rooturl);
    }

    /**
     * @param $rooturl
     */
    protected function set_root_url($rooturl) {
        global $CFG;
        if (!file_exists("{$CFG->dirroot}{$rooturl}")) {
            throw new \moodle_exception('pagesdirectorydoesnotexist', 'local_cltools', '', $rooturl);
        }
        $this->rooturl = $rooturl;
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    protected function get_root_url() {
        return $this->rooturl;
    }

    /**
     * @return moodle_url
     * @throws \ReflectionException
     */
    public function get_list_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/index.php");
    }

    /**
     * @return moodle_url
     * @throws \ReflectionException
     */
    public function get_add_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/add.php");
    }

    /**
     * @return moodle_url
     * @throws \ReflectionException
     */
    public function get_delete_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/delete.php");
    }

    public function get_edit_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/edit.php");
    }

    /**
     * @return moodle_url
     * @throws \ReflectionException
     */
    public function get_view_url() {
        $rootdir = $this->get_root_url();
        return new moodle_url("$rootdir/view.php");
    }
}
