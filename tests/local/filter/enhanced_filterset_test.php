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
namespace local_cltools\local\filter;

use advanced_testcase;
use core_table\local\filter\filter;

defined('MOODLE_INTERNAL') || die;

/**
 * Class representing a set of filters.
 *
 * @package   local_cltools
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enhanced_filterset_test extends advanced_testcase {
    /**
     * Get the optional filters.
     *
     */
    public function test_get_required_optional_filters() {
        $enhancedfilters = new enhanced_filterset(
                [
                        'opttestfilter1' => [
                                'filterclass' => numeric_comparison_filter::class,
                                'optional' => true
                        ],
                        'reqtestfilter1' => [
                                'filterclass' => numeric_comparison_filter::class,
                                'required' => true
                        ],
                        'reqtestfilter2' => [
                                'filterclass' => string_filter::class,
                        ]
                ]
        );
        $allfields = $enhancedfilters->get_all_filtertypes();
        $requiredfilters = $enhancedfilters->get_required_filters();
        $optionalfilters = $enhancedfilters->get_optional_filters();
        $this->assertCount(3, $allfields);
        $this->assertCount(2, $requiredfilters);
        $this->assertCount(1, $optionalfilters);
        $this->assertEquals(['reqtestfilter1', 'reqtestfilter2'], array_keys($requiredfilters));
    }

    /**
     *  Get the sql where / params used for filtering
     *
     * @dataProvider sql_filter_provider
     */
    public function test_get_sql_for_filter($filtersdef, $filtersvalues, $jointtype, $tableprefix, $excludedfiltersname,
            $expectedwhere, $expectedparams) {
        $enhancedfilters = new enhanced_filterset($filtersdef);
        $enhancedfilters->set_join_type($jointtype);
        foreach ($filtersvalues as $filtername => $filterval) {
            $enhancedfilters->add_filter_from_params($filtername, $filterval->jointtype, $filterval->values);
        }
        [$where, $params] = $enhancedfilters->get_sql_for_filter($tableprefix, $excludedfiltersname);
        $this->assertEquals($expectedwhere, $where);
        $this->assertEquals($expectedparams, $params);
    }

    /**
     * Values for test sql for filter
     *
     * @return array[]
     */
    public function sql_filter_provider() {
        return [
                'simplefilter' => [
                        'filtersdef' => [
                                'numericfilter1' => [
                                        'filterclass' => numeric_comparison_filter::class,
                                ],
                                'stringfilter1' => [
                                        'filterclass' => string_filter::class,
                                ],
                                'integerfilter1' => [
                                        'filterclass' => integer_filter::class,
                                ]
                        ],
                        'filtersvalues' => [
                                'numericfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ALL,
                                        'values' => [['direction' => '>', 'value' => 1]],
                                ],
                                'stringfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ALL,
                                        'values' =>
                                                ["A"]
                                ],
                                'integerfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ALL,
                                        'values' =>
                                                [1]
                                ],
                        ],
                        'jointype' => filter::JOINTYPE_ANY,
                        'tableprefix' => null,
                        'excludedfiltersname' => null,
                        'expectedwhere' => "( stringfilter1 LIKE :strp_stringfilter10  ESCAPE '\\\\' ) OR ( integerfilter1" .
                                "  =  :intg_integerfilter10 ) OR ( COALESCE(numericfilter1,0)  >  :nump_numericfilter10 )",
                        'expectedparams' => [
                                'strp_stringfilter10' => '%A%',
                                'intg_integerfilter10' => 1,
                                'nump_numericfilter10' => 1,
                        ]

                ],
                'composedmultiple' => [
                        'filtersdef' => [
                                'numericfilter1' => [
                                        'filterclass' => numeric_comparison_filter::class,
                                ],
                                'stringfilter1' => [
                                        'filterclass' => string_filter::class,
                                ],
                                'integerfilter1' => [
                                        'filterclass' => integer_filter::class,
                                ]
                        ],
                        'filtersvalues' => [
                                'numericfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ANY,
                                        'values' => [['direction' => '>', 'value' => 1], ['direction' => '<', 'value' => 1]],
                                ],
                                'stringfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ANY,
                                        'values' =>
                                                ["A", "B"]
                                ],
                                'integerfilter1' => (object) [
                                        'jointtype' => filter::JOINTYPE_ANY,
                                        'values' =>
                                                [1, 2]
                                ],
                        ],
                        'jointype' => filter::JOINTYPE_ALL,
                        'tableprefix' => null,
                        'excludedfiltersname' => null,
                        'expectedwhere' => "( stringfilter1 LIKE :strp_stringfilter10  ESCAPE '\\\\'  OR"
                                .
                                "  stringfilter1 LIKE :strp_stringfilter11  ESCAPE '\\\\' ) AND ( integerfilter1  =  :intg_integerfilter10  OR"
                                .
                                "  integerfilter1  =  :intg_integerfilter11 ) AND ( COALESCE(numericfilter1,0)  <  :nump_numericfilter10  OR"
                                . "  COALESCE(numericfilter1,0)  >  :nump_numericfilter11 )",
                        'expectedparams' => [
                                'strp_stringfilter10' => '%A%',
                                'intg_integerfilter10' => 1,
                                'nump_numericfilter10' => 1,
                                'strp_stringfilter11' => '%B%',
                                'intg_integerfilter11' => 2,
                                'nump_numericfilter11' => 1,
                        ]

                ]
        ];
    }

}

