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
 * AMD module to manage Tabulator cell editiion.
 *
 * @module   local_cltools/table/tabulator-edition.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


import {call as ajaxCall} from "core/ajax";
import Notification from 'core/notification';

/**
 * Format warning
 * @param {Object} result
 * @returns {null|String}
 */
const formatWarnings = (result) => {
    let message = null;
    if (Array.isArray(result.warnings) && result.warnings.length) {
        message = result.warnings.reduce((a, w) => (w.message + (a ? a : '')), null);
    }
    return message;
};
/**
 * Send the value back to server and send an tabulator-cell-edited event
 *
 * @param {String} tableHandler
 * @param {String} tableUniqueid
 * @param {CellComponent} data
 */
export const validateRemote = async (tableHandler, tableUniqueid, cell, value) => {
    const args = {
        handler: tableHandler,
        uniqueid: tableUniqueid,
        id: cell.getData().id,
        field: cell.getField(),
        value: value
    };
    return await Promise.race(
        ajaxCall(
            [{
                methodname: 'cltools_dynamic_table_validate_value',
                args: args
            }]
        )
    ).catch(Notification.exception).then(
        (result) => {
            const message = formatWarnings(result);
            if (message) {
                Notification.addNotification({message: message});
            }
            return result.success;
        });
};


/**
 * Send the value back to server and send an tabulator-cell-edited event
 *
 * @param {String} tableHandler
 * @param {String} tableUniqueid
 * @param {CellComponent} data
 */
export const cellEdited = (tableHandler, tableUniqueid, data) => {
    const args = {
        handler: tableHandler,
        uniqueid: tableUniqueid,
        id: data.getData().id,
        field: data.getField(),
        value: data.getValue(),
        oldvalue: data.getOldValue(),

    };
    return Promise.race(
        ajaxCall(
            [{
                methodname: 'cltools_dynamic_table_set_value',
                args: args
            }]
        )
    ).catch(Notification.exception).then(
        (result) => {
            if (result && result.success) {
                document.dispatchEvent(new CustomEvent('tabulator-cell-edited', args));
            } else {
                const message = formatWarnings(result);
                if (message) {
                    Notification.addNotification({message: message});
                }
                return false;
            }
            return true;
        });
};
