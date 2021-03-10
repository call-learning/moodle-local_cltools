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
 * AMD module to create a dynamic table a bit similar to the core
 * dynamic table but with more functionalities.
 * We use the Tabulator library.
 *
 * @module   local_cltools/table/dynamic.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Tabulator from 'local_cltools/local/table/tabulator-lazy';
import moment from 'local_cltools/local/moment-lazy';
import $ from 'jquery';
import {call as ajaxCall} from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {formatterFilterTransform} from './tabulator-converters';
import {MOODLE_FILTER_CONVERTER, JOINTYPE_ALL} from './moodle-filter-converters';
import {TABULATOR_FORMATTERS} from "./tabulator-formatters";

const rowQuery = (tableHandler, tableUniqueid, pageSize, params, initialFilters) => {
    let filters = (typeof params.filters === "undefined") ? [] : params.filters.map(
        (e) => {
            let filter = {
                'name': e.field, 'type': e.type, 'jointype': JOINTYPE_ALL, 'values': e.value
            };
            if (e.type in MOODLE_FILTER_CONVERTER) {
                const converter = MOODLE_FILTER_CONVERTER[e.type];
                filter.type = converter.to;
                if (typeof converter.transformer !== "undefined") {
                    filter.values = converter.transformer(e.value);
                }
            }
            return filter;
        }
    );
    const joinType = initialFilters ? initialFilters.jointype : JOINTYPE_ALL;
    if (initialFilters) {
        // Add initial filters to filters.
        Array.prototype.push.apply(filters, Object.values(initialFilters.filters));
    }
    const args = {
        handler: tableHandler,
        uniqueid: tableUniqueid,
        sortdata: (typeof params.sorters === "undefined") ? [] : params.sorters.map(
            (e) => {
                return {'sortby': e.field, 'sortorder': e.dir.toUpperCase()};
            }
        ),
        filters: filters,
        jointype: joinType,
        pagenumber: params.page,
        pagesize: pageSize,
        hiddencolumns: [],
        resetpreferences: false,
        firstinitial: "A",
        lastinitial: "Z"
    };
    return Promise.race(
        ajaxCall(
            [{
                methodname: 'cltools_dynamic_table_get_rows',
                args: args
            }]
        )
    ).catch(Notification.exception);
};

const ajaxResponseProcessor = function (url, params, response) {
    response.data = response.data.map(
        (rowstring) => JSON.parse(rowstring)
    );
    return response;
};

export const init = async (tabulatorelementid) => {
    const tableelement = $("#" + tabulatorelementid);
    const rowClickCallback = (e, row) => {
        $(document).trigger('tabulator-row-click', [row, tableelement.data('tableUniqueid')]);
    };
    tableInit("#" + tabulatorelementid,
        tableelement.data('tableHandler'),
        tableelement.data('tableUniqueid'),
        tableelement.data('table-pagesize'),
        tableelement.data('tableFilters'),
        rowClickCallback);
};

export const tableInit = async (
    tableElement,
    tableHandler,
    tableUniqueId,
    tablePageSize,
    tableFilters,
    rowClickCallback,
    otherOptions
) => {
    // Make sure momentjs is defined.
    if (typeof window.moment == "undefined") {
        window.moment = moment;
    }
    const placeHolderMessage = await getString('table:nodata', 'local_cltools');
    const columns = await Promise.race(ajaxCall(
        [{
            methodname: 'cltools_dynamic_table_get_columns',
            args: {
                handler: tableHandler,
                uniqueid: tableUniqueId
            }
        }])).catch(Notification.exception);

    Tabulator.prototype.extendModule("format", "formatters", TABULATOR_FORMATTERS);

    let options = {
        ajaxRequestFunc: function (url, config, params) {
            const pageSize = this.table.getPageSize();
            return rowQuery(tableHandler, tableUniqueId, pageSize, params, tableFilters);
        },
        ajaxURL: true, // If not set the RequestFunct will never be called.
        pagination: "remote",
        paginationSize: tablePageSize,
        ajaxFiltering: true,
        ajaxSorting: true,
        paginationDataReceived: {
            "last_page": "pagescount", // Change last_page parameter name to "pagescount".
        },
        ajaxResponse: ajaxResponseProcessor,
        columns: formatterFilterTransform(columns),
        layout: "fitColumns",
        placeholder: placeHolderMessage,
        rowClick: rowClickCallback ? rowClickCallback : () => null
    };
    if (otherOptions) {
        Object.assign(options, otherOptions);
    }
    new Tabulator(tableElement, options);
};