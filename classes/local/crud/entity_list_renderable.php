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
 * Renderable for entity list
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;

use moodle_url;
use renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for CompetVetEval
 *
 * @package    local_resourcelibrary
 * @copyright  2020 CALL Learning 2020 - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_list_renderable implements renderable {
    /** @var int page number */
    public $page;

    /** @var int perpage records to show */
    public $perpage;

    /** @var \moodle_url url of report page */
    public $url;

    /** @var string order to sort */
    public $order;

    /** @var persistent_list page list */
    public $entitylist;

    /**
     * Constructor
     *
     * @param string $url
     * @param int $page
     * @param int $perpage
     * @param string $order
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __construct(
        $persistentlist,
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
        $this->entitylist = $persistentlist;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->entitylist->define_baseurl($this->url);
        $this->entitylist->is_downloadable(true);
        $this->entitylist->show_download_buttons_at(array(TABLE_P_BOTTOM));
    }


    /**
     * Download logs in specified format.
     */
    public function download() {
        $filename = 'entity_list' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        $this->entitylist->is_downloading('csv', $filename);
        $this->entitylist->out($this->perpage, false);
    }
}

