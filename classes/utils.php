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

namespace local_cltools;

use context;

defined('MOODLE_INTERNAL') || die;

/**
 * Generic utils for cltools
 *
 * Set of different utilities methods that can be used in various places
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Gets the user full name helper
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $userfullname it will fetch it from db.
     *
     * @param int $userid
     * @return false|string
     */
    public static function get_user_fullname($userid) {
        global $DB;
        static $userfullnames = [];

        if (empty($userid)) {
            return false;
        }

        if (!empty($userfullnames[$userid])) {
            return $userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if (isset($userfullnames[$userid]) && $userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_record_sql($sql, $uparams)) {
            $userfullnames[$userid] = false;
            return false;
        }

        $userfullnames[$userid] = fullname($user);
        return $userfullnames[$userid];
    }

    /**
     * Get time helper
     *
     * @param int $time
     * @return string
     */
    public static function get_time($time, $download = false) {
        if (empty($download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($time, $dateformat);
    }
}
