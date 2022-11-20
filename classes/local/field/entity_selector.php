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

namespace local_cltools\local\field;

use core\persistent;
use local_cltools\local\crud\entity_utils;
use MoodleQuickForm;
use ReflectionClass;
use ReflectionException;
use renderer_base;

/**
 * Entity selector field
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_selector extends persistent_field {
    /**
     * @var persistent|null $entityclass
     */
    protected $entityclass = "";
    /**
     * @var string $displayfield
     */
    protected $displayfield = "";

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definition
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_INT,
                'default' => 0
        ];
        $fielddef = $this->init($fielnameordef, $standarddefaults);
        if (empty($fielddef->entityclass)) {
            throw new field_exception('required', 'entityclass');
        }
        if (empty($fielddef->displayfield)) {
            throw new field_exception('required', 'displayfield');
        }
        $this->entityclass = $fielddef->entityclass;
        $this->displayfield = $fielddef->displayfield;
    }

    /**
     * Get the matching formatter type to be used for display
     *
     * @link  http://tabulator.info/docs/4.9/format
     * @return object|null return the parameters (or null if no matching formatter)
     *
     */
    public function get_column_formatter(): ?object {
        $format = parent::get_column_formatter();
        $format->formatter = 'entity_lookup';
        $format->formatterParams = (object) [
                'entityclass' => $this->entityclass,
                'displayfield' => $this->displayfield
        ];
        return $format;
    }

    /**
     * Get the matching editor type to be used in the table
     *
     * @link  http://tabulator.info/docs/4.9/editor
     * @return object|null return the parameters (or null if no matching editor)
     *
     */
    public function get_column_editor(): ?object {
        return (object) [
                'editor' => 'entity_lookup',
                'editorParams' => (object) [
                        'entityclass' => $this->entityclass,
                        'displayfield' => $this->displayfield
                ]
        ];
    }

    /**
     * Get additional joins and fields
     *
     * Not necessary most of the time
     *
     * @param string $entityalias
     * @return string
     * @throws ReflectionException
     */
    public function get_additional_from(string $entityalias = 'e'): string {
        $table = ($this->entityclass)::TABLE;
        $reflectionclass = new ReflectionClass($this->entityclass);
        $aliasname = entity_utils::get_persistent_prefix($reflectionclass);
        return "LEFT JOIN {" . $table . "} $aliasname ON {$aliasname}.id = {$entityalias}.{$this->fieldname}";
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void {
        $values = static::entity_lookup($this->entityclass, $this->displayfield);
        $choices = [];
        foreach ($values as $val) {
            $choices[$val['id']] = $val['value'];
        }
        $mform->addElement($this->get_form_field_type(), $this->get_name(), $this->get_display_name(), $choices);
        parent::internal_form_add_element($mform);
    }

    /**
     * Generic entity lookup.
     *
     * Used in external API.
     *
     * @param string $entityclass
     * @param string $displayfield
     * @return array
     */
    public static function entity_lookup(string $entityclass, string $displayfield): array {
        if ($entityclass && class_exists($entityclass)) {
            $fields = entity_utils::get_defined_fields($entityclass);
            if (empty($displayfield)) {
                foreach ($fields as $field) {
                    if (in_array($field->get_name(), ['shortname', 'idnumber'])) {
                        $displayfield = $field->get_name();
                    }
                }
            }
            $records = $entityclass::get_records(null, $displayfield, 'ASC');
            return array_map(function($r) use ($displayfield) {
                return ['id' => $r->get('id'), 'value' => $r->get($displayfield)];
            }, $records);
        } else {
            return [];
        }
    }

    /**
     * Get form field type
     *
     * @return string
     */
    public function get_form_field_type(): string {
        return "searchableselector";
    }
    /**
     * Return a printable version of the value provided in input
     *
     * @param persistent|null $persistent
     * @param renderer_base|null $renderer
     * @return string
     */
    public function format_value(?persistent $persistent = null, ?renderer_base $renderer = null): string {
        $value = parent::format_value($persistent, $renderer);
        if (!empty($value) && is_numeric($value)) {
            $record = $this->entityclass::get_record(['id' => $value], 'ASC');
            return $record->get($this->displayfield);
        }
        return $value;
    }
}
