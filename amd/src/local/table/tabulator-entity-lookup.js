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
 * AMD module to store the entity lookup values
 *
 * @module   local_cltools/table/tabulator-entity-lookup.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {entityTableLookup} from "./repository";


const ENTITY_LOOKUP_PREFIX = "entityLookup";

/**
 * Compute Prefix
 * @param {string} entityclass
 * @param {string} displayfield
 * @return {string}
 */
const computePrefix = (entityclass, displayfield) => ENTITY_LOOKUP_PREFIX + "_" + entityclass + "_" + displayfield;
/**
 * Entity Lookup
 * @param {string} entityclass
 * @param {string} displayfield
 * @return {Array}
 */
export const entityLookup = (entityclass, displayfield) => {
    const values = sessionStorage.getItem(computePrefix(entityclass, displayfield));
    return values ? JSON.parse(values) : [];
};

/**
 * Entity Lookup preparation
 * @param {string} entityclass
 * @param {string} displayfield
 * @return {void}
 */
export const prepareEntityLookup = async(entityclass, displayfield) => {
    window.onbeforeunload = () => {
        sessionStorage.removeItem(computePrefix(entityclass, displayfield));
    };
    const lookupValues = await entityTableLookup({
        entityclass: entityclass,
        displayfield: displayfield,
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
    sessionStorage.setItem(computePrefix(entityclass, displayfield), JSON.stringify(lookupValues));
};
