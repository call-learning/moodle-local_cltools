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
 * AMD module defining new Tabulator formatters
 *
 * @module   local_cltools/table/tabulator-formatter.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import moment from 'local_cltools/local/moment-lazy';
import {entityLookup} from "./tabulator-entity-lookup";

/**
 * dateEditor
 *
 * @param cell
 * @param onRendered
 * @param success
 * @returns {HTMLInputElement}
 */
export const dateEditor = (cell, onRendered, success) => {
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


/**
 * Table EDITOR
 * @type {{date: (function(*, *, *): HTMLInputElement), entity_lookup: (function(*=, *=, *=, *=, *): *)}}
 */
export const TABULATOR_EDITORS = {
    date: dateEditor,
    'entity_lookup': (cell, onRendered, success, cancel, editorParams) => {
        return this.editor.select(cell, onRendered, success, cancel,
            entityLookup(editorParams.entityclass, editorParams.displayfield));
    }
};