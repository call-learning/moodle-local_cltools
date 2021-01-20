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
use moodle_url;
/**
 * Class crud_helper. Edit an entity.
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_edit extends base {
    /**
     * Current Action
     */
    const ACTION = 'edit';
    /**
     * Action done string
     */
    const ACTION_DONE = 'edited';

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
        // Add a new entity or edit it.
        $entity = null;
        $mform = null;
        $id = required_param('id', PARAM_INT);
        $entity = $this->refpersistentclass->newInstance($id);
        $mform = $this->instanciate_related_form(null,
            ['persistent' => $entity]);
        $mform->prepare_for_files();
        if ($mform->is_cancelled()) {
            redirect($this->persistentnavigation->get_list_url());
        } else if ($data = $mform->get_data()) {
            try {
                $mform->save_submitted_files($data);
                $entity = $this->refpersistentclass->newInstance($data->id, $data);
                $entity->update();
                if ($postprocesscb && is_callable($postprocesscb)) {
                    $postprocesscb($entity, $data);
                }
                $this->trigger_event($entity);
                redirect(
                    new moodle_url($this->persistentnavigation->get_view_url(), ['id' => $entity->get('id')]),
                    $this->get_action_event_description(),
                    null,
                    $messagetype = \core\output\notification::NOTIFY_SUCCESS);
            } catch (\moodle_exception $e) {
                $returnedtext .= $this->renderer->notification($e->getMessage(), 'notifyfailure');
            }
        }
        $returnedtext .= $mform->render();

        return $returnedtext;
    }
}