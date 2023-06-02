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

use cache;
use cache_store;
use context_system;
use core\persistent;
use moodle_exception;
use MoodleQuickForm;
use renderer_base;
use required_capability_exception;

/**
 * Select a user or a course or a given moodle entity
 *
 * @package   local_cltools
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generic_selector extends persistent_field {
    /**
     * @var string|null $mtype
     */
    protected $mtype = "";

    /**
     * Construct the field from its definition
     *
     * @param string|array $fielnameordef there is a shortform with defaults for boolean field and a long form with all or a partial
     * definiton
     */
    public function __construct($fielnameordef) {
        $standarddefaults = [
                'required' => false,
                'rawtype' => PARAM_INT,
                'default' => ''
        ];
        $this->init($fielnameordef, $standarddefaults);
        $this->mtype = empty($fielddef->type) ? 'user' : $fielddef->type;
        $this->sortable = true; // Not sortable for now.
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
        $format->formatter = 'generic_lookup';
        $format->formatterParams = (object) [
                'type' => $this->mtype,
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
                'editor' => 'generic_lookup',
                'editorParams' => (object) [
                        'type' => $this->mtype,
                ]
        ];
    }

    /**
     * Add element onto the form
     *
     * @param MoodleQuickForm $mform
     * @param mixed ...$additionalargs
     */
    public function form_add_element(MoodleQuickForm &$mform, ...$additionalargs): void {
        $values = static::get_generic_entities($this->mtype);
        $choices = [];
        foreach ($values as $val) {
            $choices[$val['id']] = $val['value'];
        }
        $mform->addElement($this->get_form_field_type(), $this->get_name(), $this->get_display_name(), $choices);
        parent::internal_form_add_element($mform);
    }

    /**
     * Get all users
     *
     * @param string $type
     * @return array
     */
    public static function get_generic_entities(string $type): array {
        global $DB;
        $context = context_system::instance();
        switch ($type) {
            case 'user':
                if (!has_capability('moodle/user:viewdetails', $context)) {
                    throw new required_capability_exception($context, 'moodle/user:viewdetails', 'cannotviewdetails',
                            'local_cltools');
                }
                $values = array_values(self::get_users());
                break;
            default:
                throw new moodle_exception('cannotfetchentity', 'local_cltools');
        }
        return $values;
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
        if (!empty($value) && is_numeric($value) && !empty($persistent)) {
            switch ($this->mtype) {
                case 'user':
                    $users = self::get_users();
                    return $users[$value]['value'] ?? '';
            }
        }
        return $value;
    }

    /**
     * Get users but cache the value.
     *
     * @return array
     */
    private static function get_users(): array {
        global $DB;
        $cache = cache::make_from_params(cache_store::MODE_APPLICATION, 'local_cltools', 'genericselectorcache');
        $values = $cache->get('users');
        if (!$values) {
            $values = [];
            foreach ($DB->get_recordset('user') as $user) {
                $userdisplay = ucwords(fullname($user)) . " ($user->email)";

                $values[$user->id] = [
                    'id' => $user->id,
                    'value' => $userdisplay
                ];
            }
            $cache->set('user', $values);
        }
        return $values;
    }
}
