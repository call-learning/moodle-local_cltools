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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/tablelib.php');

use local_cltools\local\crud\form\persistent_list_filter;
use moodle_url;
use pix_icon;
use popup_action;
use table_sql;

/**
 * Persistent list base class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_list extends table_sql {

    /** @var array list of user fullname shown in report. This is a way to store temporarilly the usernames and
     * avoid hitting the DB too much
     */
    private $userfullnames = array();

    /** @var persistent_list_filter $filterform filterform */
    private $filterform = null;

    protected static $persistentclass = null;

    protected $actionsdefs = [];

    /**
     * Sets up the page_table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param string $persistentclass
     * @param persistent_list_filter $filterform (optional) filter form.
     * @throws \coding_exception
     * @see page_list::get_filter_definition() for filter definition
     */
    public function __construct($uniqueid,
        $actionsdefs = null
    ) {
        parent::__construct($uniqueid);

        // Create the related persistent filter form.

        $refpersistentfiltersclass = (new \ReflectionClass(static::$persistentclass))->getNamespaceName() . "\\list_filters";
        $this->filterform = new $refpersistentfiltersclass($this);
        $cols = [];
        $headers = [];

        $persistentprefix = persistent_utils::get_persistent_prefix(static::$persistentclass);

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
            if (persistent_utils::is_reserved_property($name) || !(empty($existingproperties[$name]))) {
                continue;
            }
            $label = persistent_utils::get_string_for_entity(static::$persistentclass, $name);
            $existingproperties[$name] = (object) [
                'fullname' => $label
            ];
        }
    }

    /**
     * Get associated filter form
     *
     * @return persistent_list_filter|null
     */
    public function get_filter_form() {
        return $this->filterform;
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
        $imagesurls = persistent_utils::get_files_urls(
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
     * Build a search query for the given field
     *
     * @param string $fieldname
     * @param array $joins
     * @param array $params
     */
    protected function build_search_filter($fieldname, $def, $searchedvalue, &$joins, &$params) {
        global $DB;
        // TODO : deal with SQL in or sql = for int.
        switch ($def->datatype) {
            case 'int':
                $joins[] = "$fieldname =:$fieldname";
                $params[$fieldname] = $searchedvalue;
                break;
            case 'text':
                $joins[] = $DB->sql_like($fieldname, ':' . $fieldname, false, false);
                $params[$fieldname] = '%' . $DB->sql_like_escape($searchedvalue) . '%';
        }
    }

    /**
     * Query the database. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $joins = array('1=1');
        $params = array();

        $orderby = '';
        if ($this->filterform) {
            if ($data = $this->filterform->get_data()) {
                $filterdefinitions = $this->filterform->get_filter_definition();
                foreach ($filterdefinitions as $key => $filterdef) {
                    if ($key != 'orderby') {
                        if (!empty($data->$key)) {
                            $this->build_search_filter($key, $filterdef, $data->$key, $joins, $params);
                        }
                    } else {
                        $orderby = ''; // TODO Deal with this case.
                    }
                }
            }
        }
        $selector = implode(' AND ', $joins);

        if (!$this->is_downloading()) {
            $total = $DB->count_records_select(static::$persistentclass::TABLE, $selector, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        // Get all matching data.
        $this->rawdata = $DB->get_recordset_select(static::$persistentclass::TABLE,
            $selector,
            $params,
            $orderby,
            '*',
            $this->get_page_start(),
            $this->get_page_size());

        // Set initial bars.
        if ($useinitialsbar && !$this->is_downloading()) {
            $this->initialbars($total > $pagesize);
        }
    }
}
