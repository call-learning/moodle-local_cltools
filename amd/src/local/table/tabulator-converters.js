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
import {validateRemote} from './tabulator-edition';
import {entityLookup} from "./tabulator-entity-lookup";

export const columnSetup = async (columndefs, tableHandler, tableUniqueId) => {
    const TABULATOR_CONVERTER = {
        'formatter': {},
        'filter': {
            'select': {
                transformer: (coldef) => {
                    coldef['headerFilterFunc'] = '=';
                    return coldef;
                }
            },
            'entity_lookup': {
                to: 'autocomplete',
                transformer: (coldef) => {
                    const entityClass = coldef.filterParams.entityclass;
                    const displayField = coldef.filterParams.displayfield;
                    coldef.filterParams = {values: entityLookup(entityClass, displayField)};
                    coldef.headerFilterFunc = '=';
                    coldef.showListOnEmpty = true;
                    coldef.allowEmpty = true;
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
        'editor': {}
    };
    // Prepare entity list (this can be huge).
    for await (const columndef of columndefs) {
        for (const colprop in columndef) {
            if (colprop in TABULATOR_CONVERTER) {
                if ((colprop + "Params") in columndef) {
                    // Decode as it is JSON based encoded.
                    columndef[colprop + "Params"] = JSON.parse(columndef[colprop + "Params"]);
                }
                if (columndef[colprop] === 'entity_lookup') {
                    const params = columndef[colprop + "Params"];
                    await entityLookup(params.entityclass, params.displayfield);
                }
            }
        }
    }
    columndefs =  columndefs.map(
        (columndef) => {
            for (const colprop in columndef) {
                if (colprop in TABULATOR_CONVERTER) {
                    const tabconverter = TABULATOR_CONVERTER[colprop];
                    if (columndef[colprop] in tabconverter) {
                        const converter = tabconverter[columndef[colprop]];
                        if (converter.to) {
                            columndef[colprop] = converter.to;
                        }
                        if (converter.transformer) {
                            columndef = converter.transformer(columndef);
                        }
                    }
                }
            }

            // Preload all values for entityselector
            // Make sure filters are in fact headfilters.
            if (columndef.filter) {
                columndef.headerFilter = columndef.filter;
            }
            if (columndef.filterParams) {
                columndef.headerFilterParams = columndef.filterParams;
            }
            return columndef;
        }
    );
    return columndefs;
};