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
 * AMD module to convert between Moodle fields and format and Tabulator formats.
 *
 * @module   local_cltools/table/dynamic.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import moment from 'local_cltools/local/moment-lazy';

const dateEditor = (cell, onRendered, success) => {
    // Create and style editor.
    var editor = document.createElement("input");

    editor.setAttribute("type", "date");

    // Create and style input
    editor.style.padding = "3px";
    editor.style.width = "100%";
    editor.style.boxSizing = "border-box";

    // Set value of editor to the current value of the cell.
    editor.value = moment(cell.getValue(), "DD/MM/YYYY").format("YYYY-MM-DD");

    // Set focus on the select box when the editor is selected (timeout allows for editor to be added to DOM)
    onRendered(function() {
        editor.focus();
        editor.style.css = "100%";
    });

    // When the value has been set, trigger the cell to update
    function successFunc() {
        success(moment(editor.value, "YYYY-MM-DD").format("DD/MM/YYYY"));
    }

    editor.addEventListener("change", successFunc);
    editor.addEventListener("blur", successFunc);

    // Return the editor element
    return editor;
};

const TABULATOR_FILTER_CONVERTER = {
    'text': {
        to: 'input'
    },
    'select_choice': {
        to: 'select',
        transformer: (args) => {
            return {values: args.choices};
        },
        headerFilterFunc: "="
    },
    'entity_selector': {
        to: 'select',
        transformer: (args) => {
            return {values: args.choices};
        },
        headerFilterFunc: "="
    },
    'datetime': {
        to: 'datetime',
        transformer: (args) => {
            return {
                'outputFormat': args.inputformat,
                'inputFormat': args.inputformat,
                'timezone': args.timezone
            };
        },
        editor: dateEditor
    }
};
const TABULATOR_FORMATTER_CONVERTER = {
    'select_choice': {
        to: 'lookup',
        transformer: (args) => args.choices
    },
    'entity_selector': {
        to: 'lookup',
        transformer: (args) => args.choices
    },
    'datetime': {
        to: 'datetime',
        transformer: (args) => {
            return {
                'outputFormat': args.inputformat,
                'inputFormat': args.inputformat,
                'timezone': args.timezone
            };
        }
    }
};

export const formatterFilterTransform = (columndefs) => {
    return columndefs.map(
        (columndef) => {
            const formatterParams = ('formatterparams' in columndef) ? JSON.parse(columndef.formatterparams) : null;
            const filterParams = ('filterparams' in columndef) ? JSON.parse(columndef.filterparams) : null;
            if (!formatterParams) {
                delete columndef.formatterparams;
            }
            if (!filterParams) {
                delete columndef.filterparams;
            }
            if (('formatter' in columndef) && (columndef.formatter in TABULATOR_FORMATTER_CONVERTER)) {
                const converter = TABULATOR_FORMATTER_CONVERTER[columndef.formatter];
                if (formatterParams) {
                    columndef.formatterParams = converter.transformer(formatterParams);
                }
                columndef.formatter = converter.to;
            }
            if (('filter' in columndef) && (columndef.filter in TABULATOR_FILTER_CONVERTER)) {
                const converter = TABULATOR_FILTER_CONVERTER[columndef.filter];
                if (filterParams) {
                    columndef.headerFilterParams = converter.transformer(filterParams);
                }
                columndef.headerFilter = converter.to;
                if ('editor' in converter) {
                    columndef.editor = converter.editor;
                }
                if ('headerFilterFunc' in converter) {
                    columndef.headerFilterFunc = converter.headerFilterFunc;
                }
            }
            return columndef;
        }
    );
};