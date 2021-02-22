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
 * Persistent object list
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use html_writer;
use moodle_url;
use pix_icon;
use popup_action;
use table_sql;

/**
 * Persistent list base class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_table extends table_sql {

    /** @var array list of user fullname shown in report. This is a way to store temporarilly the usernames and
     * avoid hitting the DB too much
     */
    private $userfullnames = array();

    protected static $persistentclass = null;

    protected $actionsdefs = [];

    /**
     * Sets up the page_table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @throws \coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid,
        $actionsdefs = null
    ) {
        parent::__construct($uniqueid);

        // Create the related persistent filter form.

        $cols = [];
        $headers = [];

        $persistentprefix = entity_utils::get_persistent_prefix(static::$persistentclass);

        foreach (static::define_properties() as $name => $prop) {
            $cols[] = $name;
            if ($prop && !empty($prop->fullname)) {
                $headers[] = $prop->fullname;
            } else {
                $headers[] = get_string($persistentprefix . ':' . $name, 'local_cltools');
            }

        }
        $cols[] = 'actions';
        $headers[] = get_string('actions', 'local_cltools');
        $this->define_columns($cols);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->set_attribute('class', 'generaltable generalbox table-sm');
        $this->actionsdefs = $actionsdefs;
        $this->set_entity_sql();
    }

    /**
     * Set SQL parameters (where, from,....) from the entity
     *
     * This can be overridden when we are looking at linked entities.
     */
    protected function set_entity_sql() {
        $sqlfields = forward_static_call([static::$persistentclass, 'get_sql_fields'], 'entity', '');
        $from = static::$persistentclass::TABLE;
        $this->set_sql($sqlfields,'{'.$from.'} entity','1=1', []);
    }
    /**
     * Default property definition
     *
     * Add all the fields from persistent class except the reserved ones
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function define_properties() {
        $props = [];
        static::add_all_definition_from_persistent($props);
        return $props;
    }

    /**
     * Add all the fields from persistent class except the reserved ones
     *
     * @return array
     * @throws \ReflectionException
     */
    protected static function add_all_definition_from_persistent(&$existingproperties) {

        foreach (static::$persistentclass::properties_definition() as $name => $prop) {
            if (entity_utils::is_reserved_property($name) || !(empty($existingproperties[$name]))) {
                continue;
            }
            $label = entity_utils::get_string_for_entity(static::$persistentclass, $name);
            $existingproperties[$name] = (object) [
                'fullname' => $label
            ];
        }
    }


    /**
     * Gets the user full name helper
     *
     * This function is useful because, in the unlikely case that the user is
     * not already loaded in $this->userfullname it will fetch it from db.
     *
     * @param int $userid
     * @return false|\lang_string|mixed|string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_user_fullname($userid) {
        global $DB;

        if (empty($userid)) {
            return false;
        }

        if (!empty($this->userfullnames[$userid])) {
            return $this->userfullnames[$userid];
        }

        // We already looked for the user and it does not exist.
        if (isset($this->userfullnames[$userid]) && $this->userfullnames[$userid] === false) {
            return false;
        }

        // If we reach that point new users logs have been generated since the last users db query.
        list($usql, $uparams) = $DB->get_in_or_equal($userid);
        $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql;
        if (!$user = $DB->get_record_sql($sql, $uparams)) {
            $this->userfullnames[$userid] = false;
            return false;
        }

        $this->userfullnames[$userid] = fullname($user);
        return $this->userfullnames[$userid];
    }

    /**
     * Get time helper
     *
     * @param $time
     * @return string
     * @throws \coding_exception
     */
    protected function get_time($time) {
        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($time, $dateformat);
    }

    /**
     * Format the actions cell.
     *
     * @param $row
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function col_actions($row) {
        global $OUTPUT;
        $actions = [];
        foreach ($this->actionsdefs as $k => $a) {
            $url = new moodle_url($a->url, ['id' => $row->id]);
            $popupaction = empty($a->popup) ? null :
                new popup_action('click', $url);
            $actions[] = $OUTPUT->action_icon(
                $url,
                new pix_icon($a->icon,
                    get_string($k, 'local_cltools')),
                $popupaction
            );
        }

        return implode('&nbsp;', $actions);
    }


    /**
     * Utility to get the relevant files for a givent entity
     *
     * @param object $entity
     * @return string
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function internal_col_files($entity, $entityfilearea, $entityfilecomponent, $altmessage = 'entity-image') {
        $imagesurls = entity_utils::get_files_urls(
            $entity->id,
            $entityfilearea,
            $entityfilecomponent);
        $imageshtml = '';
        foreach ($imagesurls as $src) {
            $imageshtml .= \html_writer::img($src, $altmessage, array('class' => 'img-thumbnail'));
        }
        return $imageshtml;
    }

    /**
     * Get the dynamic table end wrapper.
     *
     * This ones has a specificity that will help with entities/crud.
     *
     * @return string
     */
    protected function get_dynamic_table_html_end(): string {
        global $PAGE;

        if (is_a($this, \core_table\dynamic::class)) {
            $PAGE->requires->js_call_amd('local_cltools/entity_dynamic_table', 'init');
            return html_writer::end_tag('div');
        }

        return '';
    }
}
