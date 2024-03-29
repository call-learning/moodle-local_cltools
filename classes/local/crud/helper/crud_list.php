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
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\helper;

use coding_exception;
use core_renderer;
use dml_exception;
use local_cltools\output\table\entity_table_renderable;
use moodle_exception;
use moodle_page;
use ReflectionException;
use single_button;

/**
 * Class crud_helper
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_list extends base {
    /**
     * Current Action
     */
    const ACTION = 'list';
    /**
     * Action done string
     */
    const ACTION_DONE = 'listed';

    /**
     * crud_helper constructor.
     *
     * @param string $entityclassname
     * @param string $action
     * @param core_renderer $renderer
     * @throws ReflectionException
     */
    public function __construct(string $entityclassname,
            $entityprefix = null,
            $formclassname = null,
            $listclassname = null,
            $exporterclassname = null,
            $persistentnavigation = null,
            $pagesrooturl = null
    ) {
        parent::__construct($entityclassname, $entityprefix, $formclassname, $listclassname,
                $exporterclassname, $persistentnavigation);
        $this->actionurl = $this->persistentnavigation->get_list_url();
    }

    /**
     * Page setup
     *
     * @param moodle_page $page
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function setup_page(&$page) {
        parent::setup_page($page);
        $formclass = $this->get_related_class("form");
        if ($formclass) {
            $buttonadd = new single_button($this->persistentnavigation->get_add_url(), get_string('add'));
            $page->set_button($this->renderer->render($buttonadd));
        }
        $this->setup_page_navigation($page);
    }

    /**
     * Process the action
     *
     * @param null $postprocesscb
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws ReflectionException
     */
    public function action_process($postprocesscb = null) {
        $returnedtext = '';
        $entitylist = $this->instanciate_related_persistent_table();
        // Here the form is just the filter form.
        $renderable = new entity_table_renderable($entitylist);

        /* @var entity_table_renderable entity table */
        $returnedtext .= $this->renderer->render($renderable);

        return $returnedtext;
    }

}
