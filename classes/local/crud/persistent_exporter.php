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
 * Persistent utils class
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use renderer_base;

/**
 * Class persistent_exporter
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class persistent_exporter extends \core\external\persistent_exporter {

    /**
     * Persistent component
     *
     * @var null
     */
    protected $persistentcomponent = null;

    /**
     * Persistent context
     *
     * @var null
     */
    protected $persistentcontext = null;

    /**
     * @var null
     */
    protected $instanceid = null;


    /** @var string The fully qualified classname. */
    protected static $persistentclass = null;

    /**
     * persistent_exporter constructor.
     *
     * @param \core\persistent $persistent
     * @param array $related
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(\core\persistent $persistent, $related = array()) {
        $this->persistentcomponent = persistent_utils::get_component(get_class($persistent));
        $this->instanceid = (int) $persistent->get('id');
        $this->persistentcontext = \context_system::instance();
        if (method_exists($persistent, 'get_context')) {
            $this->persistentcontext = $persistent->get_context();
        }
        $related = array_merge($related,
            [
                'context' => $this->persistentcontext,
                'component' => $this->persistentcomponent,
                'itemid' => (int) $persistent->get('id')
            ]
        );
        parent::__construct($persistent, $related);
    }

    protected static function define_related() {
        return array('context' => '\\context', 'component' => 'string?', 'itemid' => 'int?');
    }

    /**
     * Get value for persistent fields
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output) {
        $values = [];
        $values['usermodifiedfullname'] = fullname($this->data->usermodified);
        return $values;
    }

    /**
     * Export linked file
     *
     * @param $filearea
     * @param null $fileprefix
     * @param null $filetypegroup
     * @return \moodle_url|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function export_file($filearea, $fileprefix = null, $filetypegroup = null) {
        // Retrieve the file from the Files API.
        $files = persistent_utils::get_files($this->instanceid, $filearea, $this->persistentcomponent, $this->persistentcontext);
        $returnedfiled = null;
        foreach ($files as $file) {
            $foundfile = $fileprefix && strpos($file->get_filename(), $fileprefix) !== false;
            $foundfile = $foundfile || ($filetypegroup &&
                    file_mimetype_in_typegroup($file->get_mimetype(), $filetypegroup));
            if ($foundfile) {
                $returnedfiled = $file;
                break;
            }
        }
        if (!$returnedfiled) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $this->persistentcontext->id,
            $this->persistentcomponent,
            $filearea,
            $returnedfiled->get_itemid(),
            $returnedfiled->get_filepath(),
            $returnedfiled->get_filename()
        );
    }

    /**
     * Returns the specific class the persistent should be an instance of.
     *
     * @return string
     */
    protected static function define_class() {
        if (empty(static::$persistentclass) || !class_exists(static::$persistentclass)) {
            throw new coding_exception('Invalid class for persistent, it either does not exist or is empty ' .
                'got: ' . get_class(static::$persistentclasssistent));
        }
        return static::$persistentclass;
    }
}