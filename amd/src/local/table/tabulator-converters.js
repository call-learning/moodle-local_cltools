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
import {validateRemote} from './tabulator-edition';

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
    onRendered(function () {
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

export const formatterFilterTransform = (columndefs, tableHandler, tableUniqueId) => {
    const TABULATOR_CONVERTER = {
        'formatter': {},
        'filter': {
            'date': {
                to: dateEditor
            },
            'select': {
                transformer: (coldef) => {
                    coldef['headerFilterFunc'] = '=';
                    return coldef;
                }
            }
        },
        'validator': {
            'remote': {
                to: (cell, value) =>
                    validateRemote(cell, value, tableHandler, tableUniqueId)
            }
        },
        'editor': {
            'date': {
                to: dateEditor
            }
        }
    };
    return columndefs.map(
        (columndef) => {
            for (const colprop in columndef) {
                if (colprop in TABULATOR_CONVERTER) {
                    const tabconverter = TABULATOR_CONVERTER[colprop];
                    if ((colprop + "Params") in columndef) {
                        // Decode as it is JSON based encoded.
                        columndef[colprop + "Params"] = JSON.parse(columndef[colprop + "Params"]);
                    }
                    if (columndef[colprop] in tabconverter) {
                        const converter = tabconverter[columndef[colprop]];
                        columndef[colprop] = converter.to;
                        if (converter.transformer) {
                            columndef = converter.transformer(columndef);
                        }
                    }
                }
            }
            // Make sure filters are in fact headfilters.
            if (columndef.filter)  {
                columndef.headerFilter = columndef.filter;
            }
            if (columndef.filterParams) {
                columndef.headerFilterParams = columndef.filterParams;
            }
            return columndef;
        }
    );
};