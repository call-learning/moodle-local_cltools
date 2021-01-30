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
defined('MOODLE_INTERNAL') || die();

use core\persistent;
use local_cltools\local\crud\entity_list_renderable;
use local_cltools\sample\simple\entities_list;
use moodle_url;
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
     * @param \core_renderer $renderer
     * @throws \ReflectionException
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
            $exporterclassname, $persistentnavigation, $pagesrooturl);
        $this->actionurl = $this->persistentnavigation->get_list_url();
    }
    /**
     * Page setup
     *
     * @param \moodle_page $page
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function setup_page(&$page) {
        parent::setup_page($page);
        $buttonadd = new single_button($this->persistentnavigation->get_add_url(), get_string('add'));
        $page->set_button($this->renderer->render($buttonadd));
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
        $entitylist = $this->instanciate_related_persistent_list();
        // Here the form is just the filter form.
        $filterform = $entitylist->get_filter_form();
        // Get filter parameters.
        $filtervalues = [];
        foreach ($filterform->get_filter_definition() as $filtername => $filterdef) {
            $filtervalues[$filtername] = optional_param($filtername, $filterdef->default, $filterdef->datatype);
        }
        $filterform->set_data($filtervalues);

        $renderable = new entity_list_renderable($entitylist);

        /** @var entities_list entitylist */
        $returnedtext .= $filterform->render();
        $returnedtext .= $this->renderer->render($renderable);

        return $returnedtext;
    }

}