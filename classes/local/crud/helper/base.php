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

use coding_exception;
use context_system;
use core\persistent;
use core_renderer;
use dml_exception;
use html_writer;
use lang_string;
use local_cltools\local\crud\entity_table;
use local_cltools\local\crud\entity_utils;
use local_cltools\local\crud\form\entity_form;
use local_cltools\local\crud\generic\generic_entity_table;
use local_cltools\local\crud\navigation\flat_navigation;
use moodle_exception;
use moodle_page;
use moodle_url;
use ReflectionClass;
use ReflectionException;

/**
 * Base class for crud helper and factory
 *
 * Class helping to design very simple crud pages from given set of persistent classes.
 * This is intended to used in respective add/list/delete/edit page.
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Current Action
     */
    const ACTION = null;
    /**
     * Action done string
     */
    const ACTION_DONE = null;

    /**
     * @var persistent|null $persistent
     */
    protected $refpersistentclass = null;
    protected $refpersistentformclassname = null;
    protected $refpersistentlistclassname = null;
    protected $refpersistentexporterclassname = null;
    protected $actionurl;
    protected $persistentnavigation = null;

    protected $entityprefix = null;

    /** @var core_renderer */
    protected $renderer;

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
            $persistentnavigation = null
    ) {
        $this->refpersistentclass = new ReflectionClass($entityclassname);
        $entitynamespace = $this->refpersistentclass->getNamespaceName();
        $namespaceparts = explode('\\', $entitynamespace);
        $this->entityprefix = $entityprefix ? $entityprefix : strtolower(end($namespaceparts));
        $this->refpersistentformclassname = $formclassname;
        $this->refpersistentlistclassname = $listclassname;
        $this->refpersistentexporterclassname = $exporterclassname;
        $this->persistentnavigation = $persistentnavigation ? $persistentnavigation :
                new flat_navigation($entityclassname);
    }

    /**
     * crud_helper factory.
     *
     * @param string $entityclassname
     * @param string $action
     * @param core_renderer $renderer
     * @return mixed
     * @throws ReflectionException
     */
    public static function create(string $entityclassname,
            $action = crud_add::ACTION,
            $entityprefix = null,
            $formclassname = null,
            $listclassname = null,
            $exporterclassname = null,
            $persistentnavigation = null
    ) {
        $actionclass = "\\local_cltools\\local\\crud\\helper\\crud_$action";
        return new $actionclass($entityclassname,
                $entityprefix,
                $formclassname,
                $listclassname,
                $exporterclassname,
                $persistentnavigation
        );
    }

    /**
     * Get prefix for persistent class
     *
     * @return string
     */
    public function get_persistent_prefix() {
        return $this->entityprefix;
    }

    /**
     * Get related form
     *
     * @param $formparameters
     * @return entity_form|object
     * @throws ReflectionException*
     */
    public function instanciate_related_form($persistentid = 0, ...$formparameters) {
        $entity = null;
        if ($persistentid) {
            $entity = $this->refpersistentclass->newInstance($persistentid);
        }
        $formentity = null;
        $reflectionclass = $this->get_related_class('form');
        if ($reflectionclass) {
            $formentity = $reflectionclass->newInstanceArgs([$this->actionurl, ['persistent' => $entity]] + $formparameters);
        }
        return $formentity;
    }

    /**
     * Get related form, exporter, list class
     *
     * @param $type
     * @return object
     */
    protected function get_related_class($type) {
        $propertyname = "refpersistent{$type}classname";
        $reflectionclass = $this->get_related_persistent_reflectionclass($this->$propertyname, $type);
        return $reflectionclass;
    }

    /**
     * @param $persistentclassname
     * @param $reflectionclassname
     * @return ReflectionClass
     * @throws ReflectionException
     */
    protected function get_related_persistent_reflectionclass($persistentclassname, $reflectionclassname) {
        if ($persistentclassname) {
            return new ReflectionClass($persistentclassname);
        } else {
            $entitynamespace = $this->refpersistentclass->getNamespaceName();
            $classname = $entitynamespace . "\\$reflectionclassname";
            if (class_exists($classname)) {
                return new ReflectionClass($entitynamespace . "\\$reflectionclassname");
            }
        }
        return null;
    }

    /**
     * Get related form
     *
     * @param $formparameters
     * @return entity_form|object
     * @throws ReflectionException*
     */
    public function instanciate_related_exporter(persistent $entity) {
        $exporterentity = null;
        $reflectionclass = $this->get_related_class('exporter');
        if ($reflectionclass) {
            $exporterentity = $reflectionclass->newInstance($entity);
        }
        return $exporterentity;
    }

    /**
     * Get related list class
     *
     * @param array $formparameters
     * @return entity_table|object
     * @throws ReflectionException*
     */
    public function instanciate_related_persistent_list() {
        $uniqueid = html_writer::random_id(entity_utils::get_persistent_prefix($this->refpersistentclass));
        $actionsdefs = [
                'edit' => (object) [
                        'icon' => 't/edit',
                        'url' => $this->persistentnavigation->get_edit_url($this->refpersistentclass)
                ],
                'view' => (object) [
                        'icon' => 'e/search',
                        'url' => $this->persistentnavigation->get_view_url($this->refpersistentclass),
                ],
                'delete' => (object) [
                        'icon' => 't/delete',
                        'url' => $this->persistentnavigation->get_delete_url($this->refpersistentclass)
                ]
        ];
        $listentity = null;
        $reflectionclass = $this->get_related_class('list');
        if ($reflectionclass) {
            $listentity = $reflectionclass->newInstance($uniqueid, $actionsdefs, false, $this->refpersistentclass->getName());
        } else {
            // Default list boilerplate.
            $listentity = new generic_entity_table(null, null, false, $this->refpersistentclass->getName());
        }
        return $listentity;
    }

    /**
     * Page setup
     *
     * @param moodle_page $page
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function setup_page(&$page) {
        $header = $this->get_page_header();
        $page->set_pagelayout('standard');
        $page->set_context(context_system::instance());
        $page->set_title($header);
        $page->set_heading($header);
        $page->set_url($this->actionurl);
        if (!$this->renderer) {
            $this->renderer = $page->get_renderer('local_cltools');
        }
    }

    /**
     * Get current page header
     *
     * @return string
     * @throws ReflectionException
     * @throws coding_exception
     */
    protected function get_page_header() {
        $stringprefix = entity_utils::get_persistent_prefix($this->refpersistentclass);
        $action = get_string(static::ACTION, 'local_cltools');
        return $action . ' ' . entity_utils::get_string_for_entity($this->refpersistentclass, $stringprefix . ':entity');
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
    abstract public function action_process($postprocesscb = null);

    /**
     * Get event description as language string
     *
     * @return lang_string|string
     * @throws ReflectionException
     * @throws coding_exception
     */
    protected function get_action_event_description() {
        $entityname = entity_utils::get_persistent_prefix($this->refpersistentclass);

        return ucfirst(entity_utils::get_string_for_entity($this->refpersistentclass,
                'entity:' . static::ACTION_DONE, $entityname));
    }

    /**
     * Setup extra page navigation
     *
     * @param $page
     * @throws ReflectionException
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function setup_page_navigation($page) {
        $header = $this->get_page_header();
        $listpageurl = $this->persistentnavigation->get_list_url();
        $page->navbar->add(
                $header,
                new moodle_url($listpageurl));
    }

    /**
     * Trigger related event
     *
     * @param persistent $entity
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function trigger_event(persistent $entity) {
        $eventparams = array('objectid' => $entity->get('id'),
                'context' => context_system::instance());
        $eventclass = $this->get_action_event_class();
        if (class_exists($eventclass)) {
            $event = $eventclass::create($eventparams);
            $event->trigger();
        }
    }

    /**
     * Get event class
     *
     * @return string
     * @throws ReflectionException
     */
    protected function get_action_event_class() {
        return entity_utils::get_persistent_prefix($this->refpersistentclass) . static::ACTION_DONE;
    }

}
