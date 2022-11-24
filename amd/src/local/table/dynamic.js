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
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import {columnSetup} from './tabulator-converters';
import {cellEdited} from './tabulator-edition';
import {convertFiltersToMoodle, convertInitialFilter} from './moodle-filter-converters';
import {TABULATOR_FORMATTERS} from "./tabulator-formatters";
import {TABULATOR_EDITORS} from "./tabulator-editors";
import {getTableColumns, getTableRows} from "./repository";


const rowQuery = (tableHandler,
                  tableHandlerParams,
                  tableUniqueid,
                  pageSize,
                  params,
                  initialFilters,
                  tableEditable,
                  tableActionsDefs) => {
    let joinType;
    let filters = convertFiltersToMoodle(params.filters);
    [joinType, filters] = convertInitialFilter(initialFilters, filters);
    const args = {
        handler: tableHandler,
        handlerparams: tableHandlerParams,
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
        editable: tableEditable,
        actionsdefs: tableActionsDefs
    };
    return getTableRows(args).catch(Notification.exception);
};

const ajaxResponseProcessor = function(url, params, response) {
    response.data = response.data.map(
        (rowstring) => JSON.parse(rowstring)
    );
    return response;
};

export const init = async(tabulatorelementid) => {
    const tableelement = $("#" + tabulatorelementid);
    const rowClickCallback = (e, row) => {
        $(document).trigger('tabulator-row-click', [row, tableelement.data('tableUniqueid')]);
    };
    tableInit("#" + tabulatorelementid,
        tableelement.data('tableHandler'),
        tableelement.data('tableHandlerParams'),
        tableelement.data('tableUniqueid'),
        tableelement.data('tablePagesize'),
        tableelement.data('tableFilters'),
        rowClickCallback,
        tableelement.data('tableOtheroptions'),
        tableelement.data('tableEditable'),
        tableelement.data('tableActionsDefs'),
    );
};
export const tableInit = async(
    tableElement,
    tableHandler,
    tableHandlerParams,
    tableUniqueId,
    tablePageSize,
    tableFilters,
    rowClickCallback,
    otherOptions,
    tableEditable,
    tableActionsDefs
) => {
    let joinType, filters;
    // Make sure momentjs is defined.
    if (typeof window.moment == "undefined") {
        window.moment = moment;
    }
    const placeHolderMessage = await getString('table:nodata', 'local_cltools');
    [joinType, filters] = convertInitialFilter(tableFilters, []);
    let columns = await getTableColumns({
        handler: tableHandler,
        handlerparams: tableHandlerParams,
        uniqueid: tableUniqueId,
        filters: filters,
        jointype: joinType,
        editable: tableEditable,
        actionsdefs: tableActionsDefs
    }).catch(Notification.exception);


    Tabulator.prototype.extendModule("format", "formatters", TABULATOR_FORMATTERS);
    Tabulator.prototype.extendModule("edit", "editors", TABULATOR_EDITORS);

    columns = await columnSetup(columns, tableHandler, tableHandlerParams, tableUniqueId);

    let options = {
        ajaxRequestFunc: function(url, config, params) {
            const pageSize = this.table.getPageSize();
            return rowQuery(
                tableHandler,
                tableHandlerParams,
                tableUniqueId,
                pageSize,
                params,
                tableFilters,
                tableEditable,
                tableActionsDefs
            );
        },
        ajaxURL: true, // If not set the RequestFunct will never be called.
        pagination: "remote",
        paginationSize: tablePageSize,
        ajaxFiltering: true,
        ajaxSorting: true,
        dataFiltered: function() {
            $(document).trigger('tabulator-filter-changed', [
                    tableHandler, tableHandlerParams, tableUniqueId, this.getFilters(true)
                ]
            );
        },
        paginationDataReceived: {
            "last_page": "pagescount", // Change last_page parameter name to "pagescount".
        },
        ajaxResponse: ajaxResponseProcessor,
        cellEdited: function(data) {
            cellEdited(tableHandler, tableHandlerParams, tableUniqueId, data);
        },
        validationMode: "highlight",
        columns: columns,
        layout: "fitColumns",
        placeholder: placeHolderMessage,
        rowClick: rowClickCallback ? rowClickCallback : () => null
    };
    if (typeof otherOptions === 'object' && otherOptions !== null) {
        Object.assign(options, otherOptions);
    }
    new Tabulator(tableElement, options);
};