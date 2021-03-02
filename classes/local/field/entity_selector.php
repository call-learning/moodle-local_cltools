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
 * Base field
 *
 * For input and output
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools\local\field;
defined('MOODLE_INTERNAL') || die();

class entity_selector extends base {
    protected $entityclass = "";
    protected $displayfield = "";

    /**
     * Get the additional information related to the way we need to format this
     * information
     *
     * @return array|null associatvie array with related information on the way
     * to format the data.
     *
     * @throws \coding_exception
     */
    public function get_formatter_parameters() {
        return [
            'choices' => $this->get_entities()
        ];
    }

    /**
     * Get the additional information related to the way we need to format this
     * information
     *
     * @return array|null associatvie array with related information on the way
     * to filter the data.
     *
     */
    public function get_filter_parameters() {
        return [
            'choices' => $this->get_entities()
        ];
    }


    public function __construct($fielddef) {
        parent::__construct($fielddef);
        if (is_array($fielddef)) {
            $fielddef = (object) $fielddef;
        }
        $this->entityclass = empty($fielddef->entityclass) ? null : $fielddef->entityclass;
        $this->displayfield = empty($fielddef->displayfield) ? null : $fielddef->displayfield;
    }

    /**
     * Add element onto the form
     *
     * @param $mform
     * @param mixed ...$additionalargs
     * @return mixed
     * @throws \dml_exception
     */
    public function internal_add_form_element(&$mform, $name, $fullname) {
        $choices = $this->get_entities();
        $mform->addElement('searchableselector', $name, $fullname, $choices);
    }

    protected function get_entities() {
        global $DB;
        $entitytype = $this->entityclass;
        return $DB->get_records_menu($entitytype::TABLE, null, $fields = 'id,' . $this->displayfield);
    }
}