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
 * Renderable for entities table
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\output\table;
defined('MOODLE_INTERNAL') || die();

use dml_exception;
use local_cltools\local\crud\entity_table;
use local_cltools\local\table\dynamic_table_sql;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for dynamic table
 *
 * @package    local_resourcelibrary
 * @copyright  2020 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dynamic_table_sql_renderable implements renderable, templatable {

    /** @var int perpage records to show */
    public $perpage;

    /** @var moodle_url url of report page */
    public $url;

    /** @var string order to sort */
    public $order;

    /** @var dynamic_table_sql table */
    public $dynamictable;

    /** @var object */
    public $otheroptions;

    /**
     * Constructor
     *
     * @param dynamic_table_sql $dynamictable
     * @param string $url
     * @param int $page
     * @param int $perpage
     * @param string $order
     * @throws dml_exception
     */
    public function __construct(
        $dynamictable,
        $otheroptions = null,
        $perpage = 30
    ) {
        global $PAGE;
        $url = new moodle_url($PAGE->url);
        $this->dynamictable = $dynamictable;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->dynamictable->define_baseurl($url);
        $this->dynamictable->is_downloadable(true);
        $this->dynamictable->show_download_buttons_at(array(TABLE_P_BOTTOM));
        $this->otheroptions = $otheroptions;
    }

    /**
     * Download logs in specified format.
     */
    public function download() {
        $filename = 'page_list' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        $this->dynamictable->is_downloading('csv', $filename);
        $this->dynamictable->out($this->perpage, false);
    }

    public function export_for_template(renderer_base $output) {
        $context = new stdClass();
        $context->tableuniqueid = $this->dynamictable->uniqueid;
        $context->filtersetjson = json_encode($this->dynamictable->get_filter_set());
        $context->sortdatajson = '';
        $context->pagesize = $this->perpage;
        $context->handler = get_class($this->dynamictable);
        $context->otheroptions = "";
        $context->editable = $this->dynamictable->is_editable();
        if ($this->otheroptions) {
            $context->otheroptions = json_encode($this->otheroptions);
        }
        return $context;
    }
}
