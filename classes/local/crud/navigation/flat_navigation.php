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
use moodle_exception;
use moodle_url;
use ReflectionClass;

/**
 * Class flat_navigation :  Flat navigation class
 *
 * Implements a navigation with one file/page per action.
 *
 * @package local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flat_navigation implements persistent_navigation {
    /**
     * @var persistent|null $persistentclass current persistent class
     */
    protected $persistentclass = null;
    /**
     * @var moodle_url $rooturl Root URL
     */
    protected $rooturl = null;

    /**
     * flat_navigation constructor.
     *
     *
     * This will deduce the root url for all pages from the path of the class if rooturl not provided.
     *
     * @param string $persistentclass
     * @param moodle_url|null $rooturl
     */
    public function __construct(string $persistentclass, $rooturl = null) {
        global $CFG;
        $this->persistentclass = $persistentclass;
        if (!$rooturl) {
            // Deduce the path from the persistent class path.
            $rc = new ReflectionClass($persistentclass);
            $filepath = dirname($rc->getFileName());
            $foldername = basename($filepath);
            if (strstr($filepath, $CFG->dirroot) === false) {
                throw new moodle_exception('pagesdirectoryoutofrootdir', 'local_cltools', '', $filepath);
            }
            $filepath = str_replace($CFG->dirroot, '', $filepath);
            while ($filepath != '.' || empty($filepath)) {
                if (file_exists("{$CFG->dirroot}{$filepath}/pages/")) {
                    $rooturl = new moodle_url("{$filepath}/pages/$foldername");
                    break;
                }
                if (file_exists("{$CFG->dirroot}{$filepath}/pages/$foldername")) {
                    $rooturl = new moodle_url("{$filepath}/pages/$foldername");
                    break;
                }
                $filepath = dirname($filepath);
            }
            if (empty($rooturl)) {
                throw new moodle_exception('pagesdirectorydoesnotexist', 'local_cltools', '', $filepath);
            }
        }
        $this->set_root_url($rooturl);
    }

    /**
     * Get URL for a list of entities
     *
     * @return moodle_url
     */
    public function get_list_url(): moodle_url {
        $rooturl = $this->get_root_url();
        return new moodle_url($rooturl->get_path(true) . '/index.php', $rooturl->params());
    }

    /**
     * Get root URL
     *
     * @return moodle_url
     */
    protected function get_root_url(): moodle_url {
        return $this->rooturl;
    }

    /**
     * Set root URL
     *
     * @param moodle_url $rooturl
     */
    protected function set_root_url(moodle_url $rooturl): void {
        global $CFG;
        $this->rooturl = $rooturl;
    }

    /**
     * Get the URL for adding a new entity
     *
     * @return moodle_url
     */
    public function get_add_url(): moodle_url {
        $rooturl = $this->get_root_url();
        return new moodle_url($rooturl->get_path() . '/add.php', $rooturl->params());
    }

    /**
     * Get the URL for deleting an entity
     *
     * @return moodle_url
     */
    public function get_delete_url(): moodle_url {
        $rooturl = $this->get_root_url();
        return new moodle_url($rooturl->get_path() . '/delete.php', $rooturl->params());
    }

    /**
     * Get the URL for editing an entity
     *
     * @return moodle_url
     */
    public function get_edit_url(): moodle_url {
        $rooturl = $this->get_root_url();
        return new moodle_url($rooturl->get_path() . '/edit.php', $rooturl->params());
    }

    /**
     * Get the URL for viewing an entity
     *
     * @return moodle_url
     */
    public function get_view_url(): moodle_url {
        $rooturl = $this->get_root_url();
        return new moodle_url($rooturl->get_path() . '/view.php', $rooturl->params());
    }
}

