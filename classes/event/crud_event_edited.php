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
namespace local_cltools\event;

use core\event\base;

/**
 * Default edition event
 *
 * Message sent /created when entity is edited (this is the default event)
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_event_edited extends base {
    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:entity:edited', 'local_envf');
    }

    /**
     * Get the backup/restore table mapping for this event.
     *
     * @return string
     */
    public static function get_objectid_mapping() {
        return base::NOT_MAPPED;
    }

    /**
     * No mapping
     *
     * @return array|false
     */
    public static function get_other_mapping() {
        // Nothing to map.
        return false;
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return get_string('entity:edited', 'local_envf', "{$this->objecttable}({$this->objectid})");
    }

    /**
     * This is called after other has been set
     *
     * Hack here: as we cannot override the create method, we have to find a way to set the objecttable.
     *
     * @return void
     */
    protected function validate_data() {
        $this->data['objecttable'] = $this->data['other']['objecttable']; // We passed this through other.
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
}
