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

namespace local_cltools\local\crud\helper;
defined('MOODLE_INTERNAL') || die();

use local_cltools\local\crud\persistent_utils;
use moodle_url;

/**
 * Class crud_helper
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_delete extends base {
    /**
     * Current Action
     */
    const ACTION = 'delete';
    /**
     * Action done string
     */
    const ACTION_DONE = 'deleted';

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
        $confirm = optional_param('confirm', false, PARAM_BOOL);
        $id = required_param('id', PARAM_INT);
        if (!$confirm) {
            $deleteurl = $this->persistentnavigation->get_delete_url();
            $confirmurl =
                new moodle_url($deleteurl,
                    array('confirm' => true, 'id' => $id, 'sesskey' => sesskey()));
            $cancelurl = new moodle_url($this->persistentnavigation->get_list_url());
            $returnedtext .= $this->renderer->confirm(
                get_string('delete',
                    'local_cltools'),
                $confirmurl,
                $cancelurl
            );
        } else {
            $persistentprefix = persistent_utils::get_persistent_prefix($this->refpersistentclass);
            $entitydisplayname = get_string($persistentprefix, 'local_cltools');
            require_sesskey();
            $entity = $this->refpersistentclass->newInstance($id);
            $entity->delete();
            $this->trigger_event($entity);
            $returnedtext .= $this->renderer->notification(
                get_string('entity:deleted',
                    'local_cltools',
                    $entitydisplayname),
                'notifysuccess');
            $returnedtext .= $this->renderer->single_button($this->persistentnavigation->get_list_url(), get_string('continue'));
        }
        return $returnedtext;
    }

}