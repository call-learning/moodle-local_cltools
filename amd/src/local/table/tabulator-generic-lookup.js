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
 * AMD module to store the moodle entity lookup values (non persistent, just straight tables)
 *
 * @module   local_cltools/table/tabulator-entity-lookup.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {genericTableLookup} from "./repository";

const GENERIC_LOOKUP_PREFIX = "genericLookup";
/**
 * Compute Prefix
 * @param {string} mtype
 * @return {string}
 */
const computePrefix = (mtype) => GENERIC_LOOKUP_PREFIX + "_" + mtype;
/**
 * Moodle Entity Lookup
 * @param {string} mtype
 * @return {Array}
 */
export const genericLookup = (mtype) => {
    const values = sessionStorage.getItem(computePrefix(mtype));
    return values ? JSON.parse(values) : [];
};

/**
 * Moodle Entity Lookup preparation
 * @param {string} mtype
 */
export const prepareGenericLookup = async(mtype) => {
    window.onbeforeunload = () => {
        sessionStorage.removeItem(computePrefix(mtype));
    };
    const lookupValues = await genericTableLookup({
        type: mtype,
    }).catch(Notification.exception)
        .then(
            (result) => {
                if (result.warnings && result.warnings.length !== 0) {
                    Notification.addNotification(
                        {
                            message: result.warnings.reduce((a, w) => (a + ' ' + w.message), '')
                        }
                    );
                    return [];
                }
                return Object.fromEntries(result.values.map(({id, value}) => ([id, value])));
            });
    sessionStorage.setItem(computePrefix(mtype), JSON.stringify(lookupValues));
};
