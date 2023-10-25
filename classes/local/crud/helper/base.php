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

use context_system;
use core\persistent;
use core_renderer;
use html_writer;
use local_cltools\local\crud\entity_exporter;
use local_cltools\local\crud\entity_table;
use local_cltools\local\crud\entity_utils;
use local_cltools\local\crud\form\entity_form;
use local_cltools\local\crud\generic\generic_entity_exporter_generator;
use local_cltools\local\crud\generic\generic_entity_form_generator;
use local_cltools\local\crud\generic\generic_entity_table;
use local_cltools\local\crud\navigation\flat_navigation;
use local_cltools\local\crud\navigation\persistent_navigation;
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
     * @var ReflectionClass $refpersistentclass reference to the persistent entity class
     */
    protected $refpersistentclass = null;
    /**
     * @var ReflectionClass $refpersistentformclassname reference to the persistent entity class
     */
    protected $refpersistentformclassname = null;
    /**
     * @var ReflectionClass $refpersistenttableclassname reference to the persistent entity class
     */
    protected $refpersistenttableclassname = null;
    /**
     * @var ReflectionClass $refpersistentexporterclassname reference to the persistent entity class
     */
    protected $refpersistentexporterclassname = null;
    /**
     * @var moodle_url $actionurl action URL
     */
    protected $actionurl;
    /**
     * @var persistent_navigation $persistentnavigation navigation
     */
    protected $persistentnavigation = null;
    /**
     * @var string $entityprefix entity prefix
     */
    protected $entityprefix = null;

    /** @var core_renderer $renderer */
    protected $renderer;

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
        $this->refpersistentclass = new ReflectionClass($entityclassname);
        $entitynamespace = $this->refpersistentclass->getNamespaceName();
        $namespaceparts = explode('\\', $entitynamespace);
        $this->entityprefix = $entityprefix ?? strtolower(end($namespaceparts));
        $this->refpersistentformclassname = $formclassname;
        $this->refpersistenttableclassname = $tableclassname;
        $this->refpersistentexporterclassname = $exporterclassname;
        $this->persistentnavigation = $persistentnavigation ?? new flat_navigation($entityclassname);
    }

    /**
     * Get related form
     *
     * @param int $persistentid
     * @param mixed ...$formparameters
     * @return entity_form|object
     * @throws ReflectionException *
     */
    public function instanciate_related_form(int $persistentid = 0, ...$formparameters): object {
        $entity = null;
        if ($persistentid) {
            $entity = $this->refpersistentclass->newInstance($persistentid);
        }
        $reflectionclass = $this->get_related_class('form');
        if ($reflectionclass) {
            $formentity = $reflectionclass->newInstanceArgs([$this->actionurl, ['persistent' => $entity]] + $formparameters);
        } else {
            $formentity = generic_entity_form_generator::generate($this->refpersistentclass->getName(),
                [$this->actionurl, ['persistent' => $entity]] + $formparameters);
        }
        return $formentity;
    }

    /**
     * Get related form, exporter, list class
     *
     * @param string $type
     * @return ReflectionClass|null
     */
    protected function get_related_class(string $type): ?ReflectionClass {
        $propertyname = "refpersistent{$type}classname";
        if (!empty($this->$propertyname)) {
            return $this->get_related_persistent_reflectionclass($this->$propertyname, $type);
        }
        return null;
    }

    /**
     * Get related reflection class for entity
     *
     * @param string $persistentclassname
     * @param string $reflectionclassname
     * @return ReflectionClass
     */
    protected function get_related_persistent_reflectionclass(string $persistentclassname,
        string $reflectionclassname): ?ReflectionClass {
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
     * @param persistent $entity
     * @return entity_exporter
     */
    public function instanciate_related_exporter(persistent $entity): entity_exporter {
        $reflectionclass = $this->get_related_class('exporter');
        if (empty($reflectionclass) || !class_exists($reflectionclass)) {
            $exporterentity = generic_entity_exporter_generator::generate($entity);
        } else {
            $exporterentity = $reflectionclass->newInstance($entity);
        }
        return $exporterentity;
    }

    /**
     * Get related table class
     *
     * @param object|null $handleroptions
     * @return entity_table|object
     */
    public function instanciate_related_persistent_table(?object $handleroptions = null): entity_table {
        $uniqueid = html_writer::random_id(entity_utils::get_persistent_prefix($this->refpersistentclass));
        $actionsdefs = [
            (object) [
                'name' => 'edit',
                'icon' => 't/edit',
                'url' => $this->persistentnavigation->get_edit_url(),
            ],
            (object) [
                'name' => 'view',
                'icon' => 'e/search',
                'url' => $this->persistentnavigation->get_view_url(),
            ],
            (object) [
                'name' => 'delete',
                'icon' => 't/delete',
                'url' => $this->persistentnavigation->get_delete_url(),
            ],
        ];
        $reflectionclass = $this->get_related_class('table');
        $handleroptions = $handleroptions ?? new \stdClass();
        $handleroptions->genericpersistentclass = $this->refpersistentclass->getName();
        if ($reflectionclass) {
            $listentity = $reflectionclass->newInstance($uniqueid, $actionsdefs, false, $handleroptions);
        } else {
            // Default list boilerplate.
            $listentity = new generic_entity_table(null, $actionsdefs, false, $handleroptions);
        }
        return $listentity;
    }

    /**
     * Page setup
     *
     * @param moodle_page $page
     */
    public function setup_page(moodle_page &$page): void {
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
     */
    protected function get_page_header(): string {
        $stringprefix = entity_utils::get_persistent_prefix($this->refpersistentclass);
        $action = get_string(static::ACTION, 'local_cltools');
        return $action . ' ' . entity_utils::get_string_for_entity($this->refpersistentclass, $stringprefix . ':entity');
    }

    /**
     * Process the action
     *
     * @param mixed $postprocesscb
     * @return mixed
     */
    abstract public function action_process($postprocesscb = null);

    /**
     * Get event description as language string
     *
     * @return string
     */
    protected function get_action_event_description(): string {
        $entityname = entity_utils::get_persistent_prefix($this->refpersistentclass);

        return ucfirst(entity_utils::get_string_for_entity($this->refpersistentclass,
            'entity:' . static::ACTION_DONE, $entityname));
    }

    /**
     * Setup extra page navigation
     *
     * @param moodle_page $page
     */
    protected function setup_page_navigation($page): void {
        $listpageurl = $this->persistentnavigation->get_list_url();
        $stringprefix = entity_utils::get_persistent_prefix($this->refpersistentclass);
        $actiontitle = get_string(crud_list::ACTION, 'local_cltools');
        $actiontitle .= ' ' . entity_utils::get_string_for_entity($this->refpersistentclass, $stringprefix . ':entity');
        $page->navbar->add($actiontitle, $listpageurl);

    }

    /**
     * Trigger related event
     *
     * @param persistent $entity
     */
    protected function trigger_event(persistent $entity): void {
        $eventparams = [
            'objectid' => $entity->get('id'),
            'context' => context_system::instance(),
            'other' => ['objecttable' => $entity::TABLE],
        ];
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
     */
    private function get_action_event_class(): string {
        $globaleventclass = entity_utils::get_component($this->refpersistentclass)
            . '\\event\\'
            . entity_utils::get_persistent_prefix($this->refpersistentclass)
            . '_' . static::ACTION_DONE;
        if (class_exists($globaleventclass)) {
            return $globaleventclass;
        }
        return '\local_cltools\\event\\crud_event' . '_' . static::ACTION_DONE;
    }

    /**
     * crud_helper factory.
     *
     * @param string $entityclassname the related entity class name
     * @param string $action current action
     * @param string|null $entityprefix optional entity prefix
     * @param string|null $formclassname optional form class name
     * @param string|null $tableclassname optional table classname
     * @param string|null $exporterclassname optional exporter classname
     * @param object|null $persistentnavigation optional persitent navigation
     * @return base
     */
    public static function create(string $entityclassname,
        string $action = crud_add::ACTION,
        ?string $entityprefix = null,
        ?string $formclassname = null,
        ?string $tableclassname = null,
        ?string $exporterclassname = null,
        ?object $persistentnavigation = null
    ): base {
        $action = $action ?? 'add';
        $actionclass = "\\local_cltools\\local\\crud\\helper\\crud_$action";
        return new $actionclass($entityclassname,
            $entityprefix,
            $formclassname,
            $tableclassname,
            $exporterclassname,
            $persistentnavigation
        );
    }

}
