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

use local_cltools\local\table\dynamic_table_sql;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

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
     * @var array|mixed|null defined actions
     */
    protected $actionsdefs = [];

    /**
     * Constructor
     *
     * @param dynamic_table_sql $dynamictable
     * @param object $otheroptions
     * @param int $perpage
     */
    public function __construct(
            $dynamictable,
            $otheroptions = null,
            $perpage = 30
    ) {
        $this->dynamictable = $dynamictable;
        $this->perpage = $perpage;
        $this->otheroptions = $otheroptions;
        $this->actionsdefs = $dynamictable->get_defined_actions();
    }

    public function export_for_template(renderer_base $output) {
        $context = new stdClass();
        $context->tableuniqueid = $this->dynamictable->get_unique_id();
        $context->filtersetjson = json_encode($this->dynamictable->get_filterset());
        $context->sortdatajson = '';
        $context->pagesize = $this->perpage;
        $context->handler = get_class($this->dynamictable);
        $context->handlerparams = '';
        if (method_exists($this->dynamictable, 'get_persistent_class')) {
            $context->handlerparams = $this->dynamictable->define_class();
        }
        $context->otheroptions = "";
        $context->editable = $this->dynamictable->is_editable();
        if ($this->otheroptions) {
            $context->otheroptions = json_encode($this->otheroptions);
        }
        $context->actionsdefs = json_encode([]);
        if ($this->dynamictable->get_defined_actions()) {
            $actions = $this->dynamictable->get_defined_actions();
            $actions = array_map(function($a) {
                $a->url = !empty($a->url) ? $a->url->out(false) : '';
                return $a;
            }, $actions);
            $context->actionsdefs = json_encode($actions);
        }
        return $context;
    }
}
