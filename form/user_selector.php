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
 * User selector form field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');;
require_once($CFG->libdir . '/form/templatable_form_element.php');
require_once('HTML/QuickForm/input.php');

/**
 * Form field type for choosing a user.
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_user_selector extends \HTML_QuickForm_input implements templatable {
    use templatable_form_element {
        export_for_template as export_for_template_base;
    }
    use \local_cltools\local\form\form_element_accept;

    /**
     * @var boolean $allowmultiple Allow selecting more than one course.
     */
    protected $multiple = false;

    /**
     * Constructor
     *
     * @param string $elementname Element name
     * @param mixed $elementlabel Label(s) for an element
     * @param mixed $attributes Array of typical HTML attributes plus additional options, such as:
     *                       'multiple' - boolean multi select
     */
    public function __construct($elementname = null, $elementlabel = null, $attributes = []) {
        $this->_type = 'user_selector';
        if (!is_array($attributes)) {
            $attributes = [];
        }
        if (isset($attributes['multiple'])) {
            $this->multiple = $attributes['multiple'];
            unset($attributes['multiple']);
        }
        parent::__construct($elementname, $elementlabel, $attributes);
    }

    /**
     * Export the form for template
     *
     * @param renderer_base $output
     * @return array
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;
        $data = $this->export_for_template_base($output);
        $action = new action_link(
            new moodle_url(''),
            '',
            null,
            ['class' => 'btn btn-secondary'],
            new pix_icon('i/search', get_string('usersearch', 'local_cltools'))
        );
        $data['action'] = $action->export_for_template($output);
        $data['username'] = '';
        if ($data['value']) {
            $canviewfullnames = has_capability('moodle/site:viewfullnames', context_system::instance());
            $data['username'] = fullname(core_user::get_user($data['value']), $canviewfullnames);
        }

        $data['contextid'] = context_course::instance(SITEID)->instanceid;
        return $data;
    }

    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod,Squiz.Scope.MethodScope.Missing
    /**
     * Sets the value of the form element
     *
     * @param string $value Default value of the form element
     * @return    void
     */
    function setValue($value) {
        $this->updateAttributes(['value' => $value]);
    }
    // phpcs:enable moodle.NamingConventions.ValidFunctionName.LowercaseMethod,Squiz.Scope.MethodScope.Missing
}
