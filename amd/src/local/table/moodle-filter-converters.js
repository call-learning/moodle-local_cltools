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
 * AMD module to convert between Moodle filters and Tabulator filters.
 *
 * @module   local_cltools/table/dynamic.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


const toNumericEqual = (val) => JSON.stringify({
    direction: '=',
    value: val,
});

const MOODLE_FILTER_CONVERTER = {
    'like': {
        to: 'string_filter',
        transformer: (args) => {
            return {value: args};
        }
    },
    '=': {
        to: 'numeric_comparison_filter',
        transformer: (args) => {
            if (Array.isArray(args)) {
                return args.map(toNumericEqual);
            } else if (typeof args === 'boolean') {
                return [toNumericEqual(args ? 1 : 0)];
            } else {
                return [toNumericEqual(args)];
            }
        }
    }
};

export const JOINTYPE_ANY = 1;
export const JOINTYPE_ALL = 2;

export const convertInitialFilter = (initialFilters, existingFilters) => {
    const joinType = initialFilters ? initialFilters.jointype : JOINTYPE_ALL;
    if (initialFilters) {
        // Add initial filters to filters.
        Array.prototype.push.apply(existingFilters, Object.values(initialFilters.filters));
    }
    return [joinType, existingFilters];
};

export const convertFiltersToMoodle = (tabulatorFilters) => {
    return (typeof tabulatorFilters === "undefined") ? [] : tabulatorFilters.map(
        (e) => {
            let filter = {
                'name': e.field, 'type': e.type, 'jointype': JOINTYPE_ALL, 'values': e.value
            };
            if (e.type in MOODLE_FILTER_CONVERTER) {
                const converter = MOODLE_FILTER_CONVERTER[e.type];
                filter.type = converter.to;
                if (typeof converter.transformer !== "undefined") {
                    filter.values = converter.transformer(e.value);
                }
            }
            return filter;
        }
    );
};
