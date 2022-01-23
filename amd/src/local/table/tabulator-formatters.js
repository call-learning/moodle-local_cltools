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

export const TABULATOR_FORMATTERS = {
    uppercase: function (cell) {
        return cell.getValue().toUpperCase();
    },
    datets: function (cell, params) {
        if (cell.getValue() == 0) {
            return '';
        }
        const formatterParams = params.formatterParams;
        const currentLang = formatterParams.locale ? formatterParams.locale : 'en';
        moment.locale(currentLang);
        return moment.unix(cell.getValue()).format(formatterParams.outputFormat);
        // From Unix TS to displayable date.
    },
    datetimets: function (cell, params) {
        if (cell.getValue() == 0) {
            return '';
        }
        const formatterParams = params.formatterParams;
        const currentLang = formatterParams.locale ? formatterParams.locale : 'en';
        const timestamp = Number.parseInt(cell.getValue());
        moment.locale(currentLang);
        return moment.unix(timestamp).format(formatterParams.outputFormat); // From Unix TS to displayable date.
    },
    'entity_lookup': (cell, formatterParams) => {
        const lookup = entityLookup(formatterParams.entityclass, formatterParams.displayfield);
        const value = cell.getValue();
        return lookup[value];
    }
};