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
 * CRUD helper, to be used in the page content for add, edit, delete and list
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\crud\helper;
defined('MOODLE_INTERNAL') || die();

use core\persistent;
use local_cltools\crud\helper\base;
use local_cltools\crud\persistent_utils;
use moodle_url;
use single_button;

/**
 * Specific definition for view action
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_view extends base {
    /**
     * Current Action
     */
    const ACTION = 'view';
    /**
     * Action done string
     */
    const ACTION_DONE = 'viewed';

    /**
     * Page setup
     *
     * @param \moodle_page $page
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function setup_page(&$page) {
        parent::setup_page($page);
        $id = required_param('id', PARAM_INT);
        $buttonedit = new single_button(
            new moodle_url(persistent_navigation::get_edit_url($this->refpersistentclass), ['id' => $id]),
            get_string('edit'));
        $page->set_button($this->renderer->render($buttonedit));
        $this->setup_page_navigation($page);
    }

    /**
     * Process the action
     *
     * @param null $postprocesscb
     * @return mixed
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \ReflectionException
     */
    public function action_process($postprocesscb = null) {
        $returnedtext = '';
        $persistentprefix = persistent_utils::get_persistent_prefix($this->refpersistentclass);
        $id = required_param('id', PARAM_INT);
        $entity = $this->refpersistentclass->newInstance($id);
        $returnedtext .= $this->renderer->container_start();
        $returnedtext .= $this->renderer->render_from_template(
            'local_cltools/' . $persistentprefix,
            ($this->refpersistentexporterclass->newInstance($entity))->export($this->renderer)
        );
        $returnedtext .= $this->renderer->container_end();
        $this->trigger_event($entity);
        return $returnedtext;
    }
}