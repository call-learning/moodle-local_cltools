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
 * Page List filter form
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\crud\form;

use coding_exception;
use local_cltools\local\crud\persistent_list;
use local_cltools\local\crud\persistent_utils;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Filter selection form
 *
 * @package     local_mcms
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class persistent_list_filter extends \moodleform {
    /** @var string The fully qualified classname. */
    protected static $persistentclass = null;

    protected $filterdefintion;

    protected $persistentlist;

    /**
     * The constructor function calls the abstract function definition() and it will then
     * process and clean and attempt to validate incoming data.
     *
     * It will call your custom validate method to validate data and will also check any rules
     * you have specified in definition using addRule
     *
     * The name of the form (id attribute of the form) is automatically generated depending on
     * the name you gave the class extending moodleform. You should call your class something
     * like
     *
     * @param persistent_list $persistentlist instance of persistence list
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param mixed $customdata if your form defintion method needs access to data such as $course
     *              $cm, etc. to construct the form definition then pass it in this array. You can
     *              use globals for somethings.
     * @param string $method if you set this to anything other than 'post' then _GET and _POST will
     *               be merged and used as incoming data to the form.
     * @param string $target target frame for form submission. You will rarely use this. Don't use
     *               it if you don't need to as the target attribute is deprecated in xhtml strict.
     * @param mixed $attributes you can pass a string of html attributes here or an array.
     *               Special attribute 'data-random-ids' will randomise generated elements ids. This
     *               is necessary when there are several forms on the same page.
     *               Special attribute 'data-double-submit-protection' set to 'off' will turn off
     *               double-submit protection JavaScript - this may be necessary if your form sends
     *               downloadable files in response to a submit button, and can't call
     *               \core_form\util::form_download_complete();
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     * @throws coding_exception
     */
    public function __construct(
        $persistentlist,
        $action = null,
        $customdata = null,
        $method = 'post',
        $target = '',
        $attributes = null,
        $editable = true,
        $ajaxformdata = null) {
        if (empty(static::$persistentclass)) {
            throw new coding_exception('Static property $persistentclass must be set.');
        } else if (!is_subclass_of(static::$persistentclass, 'core\\persistent')) {
            throw new coding_exception('Static property $persistentclass is not valid.');
        }
        $this->persistentlist = $persistentlist;
        $this->filterdefintion = $this->get_filter_definition();
        parent::__construct($action,
            $customdata,
            $method,
            $target,
            $attributes,
            $editable,
            $ajaxformdata);
    }

    /**
     * Form property in order to display the right widget for the form.
     *
     * @return array|array[]
     */
    abstract public static function define_properties();

    /**
     * Return filter definition as an array
     *
     * An example of definition is:
     *      'shortname' => (object) ['type' => PARAM_TEXT, 'default' => ''],
     *      'orderby' => (object) ['type' => PARAM_TEXT,
     *      'choices' => [
     *          'title ASC' => get_string('pagefilter:title:asc', 'local_mcms'),
     *          'title DESC' => get_string('pagefilter:title:desc', 'local_mcms'),
     *          'timemodified ASC' => get_string('pagefilter:timemodified:asc', 'local_mcms'),
     *          'timemodified DESC' => get_string('pagefilter:timemodified:desc', 'local_mcms')
     *       ],
     *      'default' => 'title ASC'],
     *
     * @return array
     * @throws \coding_exception
     */
    public function get_filter_definition() {
        $properties = static::$persistentclass::properties_definition();
        $listproperties = $this->persistentlist->define_properties();
        $propertiesoverride = $this->define_properties();
        $entityprefix = persistent_utils::get_persistent_prefix(static::$persistentclass);
        $filters = [];
        $ordersbychoices = [];
        $stringmanager = get_string_manager();
        foreach ($properties as $key => $prop) {
            $propoverride = null;
            if (!empty($propertiesoverride[$key])) {
                $propoverride = $propertiesoverride[$key];
            }
            if (!empty($propoverride->hidden)) {
                continue; // Skip it.
            }
            $filtertype = 'text';
            if (!empty($propoverride->type)) {
                $filtertype = $propoverride->type;
            }
            $label = "";
            if (!empty($listproperties[$key]) && !empty($listproperties[$key]->fullname)) {
                $label = $listproperties[$key]->fullname;
            }
            if (!empty($propoverride->fullname)) {
                $label = $propoverride->fullname;
            } else {
                if ($stringmanager->string_exists($entityprefix . ':' . $key, 'local_cltools')) {
                    $label = get_string($entityprefix . ':' . $key, 'local_cltools');
                } else {
                    $label = get_string($key, 'local_cltools');
                }
            }
            $filters[$key] = (object) [
                'datatype' => $prop['type'],
                'type' => $filtertype,
                'options' => !empty($propoverride->options) ? $propoverride->options : null,
                'label' => $label,
                'default' => '',
            ];
            if (!empty($propoverride->sortable)) {
                $ordersbychoices["$key ASC"] =
                    $label . ' ' .
                    get_string('ascending', 'local_cltools');
                $ordersbychoices["$key DESC"] =
                    $label . ' ' .
                    get_string('descending', 'local_cltools');
            }

        }
        $filters['orderby'] = (object) [
            'datatype' => PARAM_TEXT,
            'choices' => $ordersbychoices,
            'label' => get_string('orderby', 'local_cltools'),
            'default' => !empty($ordersbychoices[0]) ? $ordersbychoices[0] : ''
        ];
        return $filters;
    }

    /**
     * The form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $entityprefix = persistent_utils::get_persistent_prefix(static::$persistentclass);
        $orderbylabel = get_string(
            "orderby", 'local_cltools');
        foreach ($this->filterdefintion as $filtername => $filterdef) {
            if (persistent_utils::is_reserved_property($filtername)) {
                continue;
            }
            $default = empty($this->_customdata[$filtername]) ? $filterdef->default : $this->_customdata[$filtername];

            if (!empty($filterdef->choices)) {
                $mform->addElement('select', $filtername,
                    $filterdef->label,
                    $filterdef->choices);

            } else {
                if ($filtername == 'orderby') {
                    // No ordering.
                    continue;
                }
                $options = !empty($filterdef->options) ? $filterdef->options : null;
                $mform->addElement($filterdef->type, $filtername, $filterdef->label,
                    $options);
            }
            $mform->setType($filtername, $filterdef->datatype);
            $mform->setDefault($filtername, $default);
        }
        // Add parent page (for structure & menu).
        $buttonarray[] = &$mform->createElement('submit', 'search',
            get_string('search'));
        $buttonarray[] = $mform->createElement('reset', 'clear', get_string('clear'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}