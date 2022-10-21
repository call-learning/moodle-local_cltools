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

use core\output\notification;
use moodle_exception;
use moodle_url;

/**
 * Class crud_helper. Add an entity.
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crud_add extends base {
    /**
     * Current Action
     */
    const ACTION = 'add';
    /**
     * Action done string
     */
    const ACTION_DONE = 'added';

    /**
     * crud_helper constructor.
     *
     * @param string $entityclassname the related entity class name
     * @param string|null $entityprefix optional entity prefix
     * @param string|null $formclassname optional form class name
     * @param string|null $tableclassname optional table classname
     * @param string|null $exporterclassname optional exporter classname
     * @param object|null $persistentnavigation optional persitent navigation
     */
    public function __construct(string $entityclassname,
            ?string $entityprefix,
            ?string $formclassname,
            ?string $tableclassname,
            ?string $exporterclassname,
            ?object $persistentnavigation
    ) {
        parent::__construct($entityclassname, $entityprefix, $formclassname, $tableclassname,
                $exporterclassname, $persistentnavigation);
        $this->actionurl = $this->persistentnavigation->get_add_url();
    }

    /**
     * Process the action
     *
     * @param callable $postprocesscb
     * @return string
     */
    public function action_process($postprocesscb = null): string {
        $returnedtext = '';
        // Add a new entity or edit it.
        $mform = $this->instanciate_related_form();
        if ($mform) {
            $mform->prepare_for_files();
            if ($mform->is_cancelled()) {
                redirect($this->persistentnavigation->get_list_url());
            } else if ($data = $mform->get_data()) {
                try {
                    $entity = $mform->save_data();

                    if ($postprocesscb && is_callable($postprocesscb)) {
                        $postprocesscb($entity, $data);
                    }
                    $this->trigger_event($entity);
                    if (!(defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                        redirect(
                                new moodle_url($this->persistentnavigation->get_view_url(), ['id' => $entity->get('id')]),
                                $this->get_action_event_description(),
                                null,
                                $messagetype = notification::NOTIFY_SUCCESS);
                    }
                } catch (moodle_exception $e) {
                    $returnedtext .= $this->renderer->notification($e->getMessage(), 'notifyfailure');
                }
            }
            $returnedtext .= $mform->render();
        } else {
            $returnedtext .=
                    $this->renderer->notification(get_string('cannotaddentity:formmissing', 'local_cltools'), 'notifyfailure')
                    . $this->renderer->continue_button($this->persistentnavigation->get_list_url());
        }
        return $returnedtext;
    }
}
