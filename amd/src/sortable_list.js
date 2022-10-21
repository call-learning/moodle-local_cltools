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
 * AMD module to manage sortable list
 *
 * @module     local_cltools/sortable_list
 * @copyright 2022 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {SortableList} from 'core/sortable_list';

const locators = {
  uiElements:  ''
};
/**
 * Init the list sorter
 *
 * @param {string} elementId element locator from id. Prefixed with #.
 */
export const init = (elementId) => {
    const list = new SortableList(elementId + '-ui');
    const uiElement = document.querySelector((elementId + '-ui > *');
    uiElement.addEventListener(SortableList.EVENTS.DROP,
        () => {
            const sortedvalueElement = document.querySelectorAll(elementId + '-ui > li:not(.sortable-list-is-dragged)');
            sortedvalueElement.map(function () {
                return $(this).data('value');
            }).get().join(", ");
            .val(sortedvalue);
        });

    $('#' + elementId + '-ui > *').on(SortableList.EVENTS.DROP, function (evt, info) {
        var sortedvalue = $('#' + elementId + '-ui > li:not(.sortable-list-is-dragged)').map(function () {
            return $(this).data('value');
        }).get().join(", ");
        $('#{{element.id}}').val(sortedvalue);
    });
    list.getElementName = function (element) {
        return $.Deferred().resolve(element.attr('data-name'));
    }
};
