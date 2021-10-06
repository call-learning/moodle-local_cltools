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

import {call as ajaxCall} from "core/ajax";
import Notification from 'core/notification';
/**
 *
 * @param formatterParams
 */
export function entityLookup(entityclass, displayfield) {
    if (!entityLookup.LOOKUP_CACHE[entityclass]) {
        entityLookup.LOOKUP_CACHE[entityclass] = [];
    }

    if (entityLookup.LOOKUP_CACHE[entityclass]
        && entityLookup.LOOKUP_CACHE[entityclass][displayfield]) {
        return entityLookup.LOOKUP_CACHE[entityclass][displayfield];
    }
    return Promise.race(
        ajaxCall(
            [{
                methodname: 'cltools_entity_lookup',
                args: {
                    entityclass: entityclass,
                    displayfield: displayfield,
                }
            }]
        )).catch(Notification.exception)
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
                entityLookup.LOOKUP_CACHE[entityclass][displayfield] = JSON.parse(result.values);
                return entityLookup.LOOKUP_CACHE[entityclass][displayfield];
            });
}

entityLookup.LOOKUP_CACHE = [];
