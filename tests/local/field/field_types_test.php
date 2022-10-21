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
 * Standard test for different field types
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_cltools\local\field;
use advanced_testcase;
use moodleform;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/cltools/tests/lib.php');
use local_cltools\simple\entity;

/**
 * Local sample form
 */
class sample_form extends moodleform {
    /**
     * Definition
     */
    protected function definition() {
        $field = $this->_customdata['field'];
        $field->form_add_element($this->_form);
    }

    /**
     * Get form elements
     * @return array
     */
    public function get_elements() {
        return $this->_form->_elements;
    }
};
/**
 * Standard test for different field types
 *
 * @package   local_cltools
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_types_test extends advanced_testcase {
    /**
     * Test data
     *
     * @return array[]
     */
    public function expected_types_values() {
        return [
                'boolean' => [
                        'field' => new boolean('fieldname'),
                        'expectations' => [
                                'format' => [
                                        [true, 'true'],
                                        [false, 'false']
                                ],
                                'type' => PARAM_BOOL,
                                'isvalid' => [
                                        [true, true], [false, false], ['true', true], ['15', false], ['Truer', false],
                                ]
                        ]
                ],
                'date' => [
                        'field' => new date('fieldname'),
                        'expectations' => [
                                'format' => [
                                        [1633229569, '3/10/21'],
                                        [1633229500, '3/10/21']
                                ],
                                'type' => PARAM_INT,
                                'isvalid' => [
                                        ['3/10/21', true], ['AAAA3/10/21', false],
                                ]
                        ]
                ],
                'datetime' => [
                        'field' => new datetime('fieldname'),
                        'expectations' => [
                                'format' => [
                                        [1633229569, '3/10/21, 10:52'],
                                        [1633229500, '3/10/21, 10:51']
                                ],
                                'type' => PARAM_INT,
                                'isvalid' => [
                                        ['3/10/21, 10:51', true], ['AAAA3/10/21', false],
                                ]
                        ]
                ],
                'editor' => [
                        'field' => new editor('fieldname'),
                        'expectations' => [
                                'format' => [
                                        ['<p>Test</p>', '<p>Test</p>']
                                ],
                                'type' => PARAM_RAW,
                                'isvalid' => [
                                        ['ABCDEF', true],
                                ]
                        ]
                ],
                'entity_selector' => [
                        'field' => new entity_selector([
                                        'fieldname' => 'fieldname',
                                        'entityclass' => entity::class,
                                        'displayfield' => 'shortname']
                        ),
                        'expectations' => [
                                'format' => [],
                                'type' => PARAM_INT,
                                'isvalid' => []
                        ]
                ],
                'files' => [
                        'field' => new files('fieldname'),
                        'expectations' => [
                                'format' => [],
                                'type' => PARAM_INT,
                                'isvalid' => []
                        ]
                ],
                'hidden' => [
                        'field' => new hidden('fieldname'),
                        'expectations' => [
                                'format' => [
                                        ['ABCDE', 'ABCDE'],
                                ],
                                'type' => PARAM_RAW,
                                'isvalid' => []
                        ]
                ],
                'html' => [
                        'field' => new html('fieldname'),
                        'expectations' => [
                                'format' => [
                                ],
                                'type' => PARAM_RAW,
                                'isvalid' => []
                        ]
                ],
                'number float' => [
                        'field' => new number('fieldname', true),
                        'expectations' => [
                                'format' => [
                                        [1, '1'],
                                        [1.1, '1.1']
                                ],
                                'type' => PARAM_FLOAT,
                                'isvalid' => []
                        ]
                ],
                'number' => [
                        'field' => new number('fieldname'),
                        'expectations' => [
                                'format' => [
                                        [1, '1'],
                                ],
                                'type' => PARAM_INT,
                                'isvalid' => []
                        ]
                ],
                'select_choice' => [
                        'field' => new select_choice(
                                ['fieldname' => 'fieldname', 'choices' => [1 => 'choice1', 2 => 'choice2']]
                        ),
                        'expectations' => [
                                'format' => [
                                        [1, 'choice1'],
                                        [2, 'choice2']
                                ],
                                'type' => PARAM_INT,
                                'isvalid' => []
                        ]
                ],
                'text' => [
                        'field' => new text('fieldname'),
                        'expectations' => [
                                'format' => [
                                        ['ABCDE', 'ABCDE'],
                                        ['<p>ABCDE</p>', "ABCDE\n"]
                                ],
                                'type' => PARAM_TEXT,
                                'isvalid' => []
                        ]
                ]

        ];
    }

    /**
     * Return a printable version of the current value
     *
     * @param persistent_field $field
     * @param array $expectations
     * @dataProvider expected_types_values
     * @covers \local_cltools\local\field\persistent_field::format_value
     */
    public function test_format_string($field, $expectations) {
        foreach ($expectations['format'] as $expectation) {
            $this->assertEquals($expectation[1], $field->format_value($expectation[0], null, null),
                    "Entry {$expectation[0]} expected {$expectation[1]}");
        }
    }

    /**
     * Get an identifier for this type of format
     *
     * @param persistent_field $field
     * @param array $expectations
     * @dataProvider expected_types_values
     * @covers \local_cltools\local\field\persistent_field::get_raw_param_type
     */
    public function test_get_raw_type($field, $expectations) {
        $this->assertEquals($expectations['type'], $field->get_raw_param_type());
    }

    /**
     * Get an identifier for this type of format
     *
     * @param persistent_field $field
     * @param array $expectations
     * @dataProvider expected_types_values
     * @covers \local_cltools\local\field\persistent_field::get_type
     */
    public function test_get_type($field, $expectations) {
        $namespaceparts = explode('\\', get_class($field));
        $this->assertEquals(end($namespaceparts), $field->get_type());
    }

    /**
     * Check if the provided value is valid for this field.
     *
     * @param persistent_field $field
     * @param array $expectations
     * @dataProvider expected_types_values
     * @covers \local_cltools\local\field\persistent_field::validate_value
     */
    public function test_validate_value($field, $expectations) {
        $fieldclass = get_class($field);
        foreach ($expectations['isvalid'] as $isvalid) {
            if (!$isvalid[1]) {
                $this->expectException(field_exception::class);
            }
            $this->assertTrue($field->validate_value($isvalid[0]) == $isvalid[1],
                    "Expected Entry {$isvalid[0]} to be valid, {$fieldclass}");
        }
    }

    /**
     * Get the display name of this field
     * @covers \local_cltools\local\field\persistent_field::get_display_name
     */
    public function test_get_display_name() {
        $field = new boolean(['fieldname' => 'fieldname', 'fullname' => 'Full Name']);
        $this->assertEquals('Full Name', $field->get_display_name());

        $field = new editor(['fieldname' => 'fieldname', 'fullname' => 'Full Name']);
        $this->assertEquals('Full Name', $field->get_display_name());
    }

    /**
     * Add element onto the form
     *
     * @param persistent_field $field
     * @param array $expectations
     * @dataProvider expected_types_values
     * @covers \local_cltools\local\field\persistent_field::get_form_field_type
     */
    public function test_add_form_element($field, $expectations) {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        $this->resetAfterTest();
        entity::delete_table();
        entity::create_table();

        $form = new sample_form(null, ['field' => $field]);
        $elements = array_filter($form->get_elements(),
                function($e) use ($field) {
                    return (in_array($e->getName(), [$field->get_name(), $field->get_name() . '_editor']));
                });
        $expectedtype = $field->get_form_field_type() == 'searchableselector' ? 'autocomplete' : $field->get_form_field_type();
        $this->assertContains(
                $expectedtype,
                array_map(
                        function($e) {
                            return $e->getType();
                        },
                        $elements
                )
        );

    }
}
