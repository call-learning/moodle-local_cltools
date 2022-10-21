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
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');;
require_once($CFG->libdir . '/form/templatable_form_element.php');
require_once('HTML/QuickForm/input.php');

/**
 * Sortable list form element.
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_sortable_list extends HTML_QuickForm_input implements templatable {
    // Include this in order to use the cltools templates.
    use \local_cltools\local\form\form_element_accept;

    /**
     * Choices
     *
     * @var array|null
     */
    protected $_choices = [];

    use templatable_form_element {
        export_for_template as export_for_template_base;
    }

    /** @var array items to display. */
    protected $items = array();

    /**
     * Constructor.
     *
     * @param string|null $elementname (optional) name of the sortable list.
     * @param string|null $elementlabel (optional) listing label.
     * @param array $attributes (optional) Either a typical HTML attribute string or an associative array.
     * @param array|null $options set of options to initalize listing.
     */
    public function __construct(?string $elementname, ?string $elementlabel, array $attributes = [], ?array $options = []) {
        $this->_type = 'sortable_list';
        parent::__construct($elementname, $elementlabel, $attributes);
        $this->_choices = $options;
    }

    /**
     * Export for relevant template
     *
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output): array {
        $context = $this->export_for_template_base($output);

        $options = [];
        foreach ($this->_choices as $value => $text) {
            $o = [
                'text' => $text,
                'value' => $value,
            ];
            $options[] = $o;
        }
        $context['options'] = $options;
        $context['name'] = $this->getName();
        return $context;
    }
}

