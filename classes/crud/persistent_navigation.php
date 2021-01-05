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
 * Persistent navigation utils class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\crud;

use moodle_url;

defined('MOODLE_INTERNAL') || die();
global $CFG;

class persistent_navigation {
    public static function get_root_url($persistentclass) {
        return '/local/cltools/pages/' . persistent_utils::get_persistent_prefix($persistentclass);
    }

    public static function get_list_url($persistentclass) {
        $rootdir = self::get_root_url($persistentclass);
        return new moodle_url("$rootdir/list.php");
    }

    public static function get_add_url($persistentclass) {
        $rootdir = self::get_root_url($persistentclass);
        return new moodle_url("$rootdir/add.php");
    }

    public static function get_delete_url($persistentclass) {
        $rootdir = self::get_root_url($persistentclass);
        return new moodle_url("$rootdir/delete.php");
    }

    public static function get_edit_url($persistentclass) {
        $rootdir = self::get_root_url($persistentclass);
        return new moodle_url("$rootdir/edit.php");
    }

    public static function get_view_url($persistentclass) {
        $rootdir = self::get_root_url($persistentclass);
        return new moodle_url("$rootdir/index.php");
    }
}