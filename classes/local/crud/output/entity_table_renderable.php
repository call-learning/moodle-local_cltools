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

namespace local_cltools\local\crud\output;

use local_cltools\local\crud\entity_table;
use moodle_url;
use renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for entities table
 *
 * @package    local_resourcelibrary
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_table_renderable implements renderable {
    /** @var int page number */
    public $page;

    /** @var int perpage records to show */
    public $perpage;

    /** @var \moodle_url url of report page */
    public $url;

    /** @var string order to sort */
    public $order;

    /** @var entity_table page list */
    public $entitytable;

    /**
     * Constructor
     *
     * @param entity_table $entitytable
     * @param string $url
     * @param int $page
     * @param int $perpage
     * @param string $order
     * @throws \dml_exception
     */
    public function __construct(
        $entitytable,
        $url = "",
        $page = 0,
        $perpage = 100
    ) {
        global $PAGE;
        // Use page url if empty.
        if (empty($url)) {
            $url = new moodle_url($PAGE->url);
        } else {
            $url = new moodle_url($url);
        }
        $this->entitytable = $entitytable;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->entitytable->define_baseurl($this->url);
        $this->entitytable->is_downloadable(true);
        $this->entitytable->show_download_buttons_at(array(TABLE_P_BOTTOM));
    }

    /**
     * Download logs in specified format.
     */
    public function download() {
        $filename = 'page_list' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        $this->entitytable->is_downloading('csv', $filename);
        $this->entitytable->out($this->perpage, false);
    }
}

