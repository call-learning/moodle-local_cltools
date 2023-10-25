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
 * Form accept routines
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\local\form;

use core\output\mustache_template_finder;
use Exception;
use MoodleQuickForm;
use templatable;

/**
 * Form accept routines trait
 *
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait form_element_accept {
    /**
     * Accepts a renderer, but tweak it to render the right local template
     *
     * @param MoodleQuickForm $renderer
     * @param bool $required Whether an element is required
     * @param string|null $error An error message associated with an element
     */
    public function accept(MoodleQuickForm &$renderer, $required = false, string $error = null) {
        // Make sure the element has an id.
        $this->_generateId();
        $advanced = isset($renderer->_advancedElements[$this->getName()]);
        $html = $this->render_from_local_template($this, $required, $advanced, $error, false, $renderer);

        if ($renderer->_inGroup) {
            $this->_groupElementTemplate = $html;
        }
        if (($renderer->_inGroup) && !empty($this->_groupElementTemplate)) {
            $this->_groupElementTemplate = $html;
        } else if (!isset($this->_templates[$this->getName()])) {
            $renderer->_templates[$this->getName()] = $html;
        }

        if (in_array($this->getName(), $renderer->_stopFieldsetElements) && $renderer->_fieldsetsOpen > 0) {
            $renderer->_html .= $renderer->_closeFieldsetTemplate;
            $renderer->_fieldsetsOpen--;
        }
        $renderer->_html .= $html;

    }

    /**
     * Render an element from this local plugin
     *
     *
     * *Ideally* we should be able to override the renderer in the local plugin.
     *
     * @param MoodleQuickForm $element
     * @param bool $required
     * @param bool $advanced
     * @param string $error
     * @param bool $ingroup
     * @return false
     */
    protected function render_from_local_template(MoodleQuickForm $element, bool $required, bool $advanced, string $error,
            bool $ingroup) {
        global $PAGE;
        $localrenderer = $PAGE->get_renderer('local_cltools');
        $templatename = 'local_cltools/element-' . $element->getType();
        if ($ingroup) {
            $templatename .= "-inline";
        }
        try {
            // We call this to generate a file not found exception if there is no template.
            // We don't want to call export_for_template if there is no template.
            mustache_template_finder::get_template_filepath($templatename);

            if ($element instanceof templatable) {
                $elementcontext = $element->export_for_template($localrenderer);

                $helpbutton = '';
                if (method_exists($element, 'getHelpButton')) {
                    $helpbutton = $element->getHelpButton();
                }
                $label = $element->getLabel();
                $text = '';
                if (method_exists($element, 'getText')) {
                    // There currently exists code that adds a form element with an empty label.
                    // If this is the case then set the label to the description.
                    if (empty($label)) {
                        $label = $element->getText();
                    } else {
                        $text = $element->getText();
                    }
                }

                // Generate the form element wrapper ids and names to pass to the template.
                // This differs between group and non-group elements.
                if ($element->getType() === 'group') {
                    // Group element.
                    // The id will be something like 'fgroup_id_NAME'. E.g. fgroup_id_mygroup.
                    $elementcontext['wrapperid'] = $elementcontext['id'];

                    // Ensure group elements pass through the group name as the element name.
                    $elementcontext['name'] = $elementcontext['groupname'];
                } else {
                    // Non grouped element.
                    // Creates an id like 'fitem_id_NAME'. E.g. fitem_id_mytextelement.
                    $elementcontext['wrapperid'] = 'fitem_' . $elementcontext['id'];
                }

                $context = [
                        'element' => $elementcontext,
                        'label' => $label,
                        'text' => $text,
                        'required' => $required,
                        'advanced' => $advanced,
                        'helpbutton' => $helpbutton,
                        'error' => $error,
                ];
                return $localrenderer->render_from_template($templatename, $context);
            }
        } catch (Exception $e) {
            // No template for this element.
            return false;
        }
    }

}
