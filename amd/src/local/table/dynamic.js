import Tabulator from 'local_cltools/local/table/tabulator-lazy';
import moment from 'local_cltools/local/moment-lazy';
import $ from 'jquery';
import {call as ajaxCall} from 'core/ajax';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const TABULATOR_FORMATTER_CONVERTER = {
    'select_choice': {
        to: 'lookup',
        transformer: (args) => args.choices
    },
    'entity_selector': {
        to: 'lookup',
        transformer: (args) => args.choices
    },
    'datetime': {
        to: 'datetime',
        transformer: (args) => {
            return {
                'outputFormat': args.inputformat,
                'inputFormat': args.inputformat,
                'timezone': args.timezone
            };
        }
    }
};

const dateEditor = (cell, onRendered, success) => {
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
    onRendered(function(){
        editor.focus();
        editor.style.css = "100%";
    });

    // When the value has been set, trigger the cell to update
    function successFunc(){
        success(moment(editor.value, "YYYY-MM-DD").format("DD/MM/YYYY"));
    }

    editor.addEventListener("change", successFunc);
    editor.addEventListener("blur", successFunc);

    // Return the editor element
    return editor;
};

const TABULATOR_FILTER_CONVERTER = {
    'text' : {
        to: 'input'
    },
    'select_choice': {
        to: 'select',
        transformer: (args) => {
            return { values: args.choices };
        }
    },
    'entity_selector': {
        to: 'select',
        transformer: (args) => {
            return { values: args.choices };
        }
    },
    'datetime': {
        to: 'datetime',
        transformer: (args) => {
            return {
                'outputFormat': args.inputformat,
                'inputFormat': args.inputformat,
                'timezone': args.timezone
            };
        },
        editor: dateEditor
    }
};
const formatterFilterTransform = (columndefs) => {
    return columndefs.map(
        (columndef) => {
            const formatterParams = ('formatterparams' in columndef) ? JSON.parse(columndef.formatterparams) : null;
            const filterParams = ('filterparams' in columndef) ? JSON.parse(columndef.filterparams) : null;
            if (!formatterParams) {
                delete columndef.formatterparams;
            }
            if (!filterParams) {
                delete columndef.filterparams;
            }
            if (('formatter' in columndef) && (columndef.formatter in TABULATOR_FORMATTER_CONVERTER)) {
                const converter = TABULATOR_FORMATTER_CONVERTER[columndef.formatter];
                if (formatterParams) {
                    columndef.formatterParams = converter.transformer(formatterParams);
                }
                columndef.formatter = converter.to;
            }
            if (('filter' in columndef) && (columndef.filter in TABULATOR_FILTER_CONVERTER)) {
                const converter = TABULATOR_FILTER_CONVERTER[columndef.filter];
                if (filterParams) {
                    columndef.headerFilterParams = converter.transformer(filterParams);
                }
                columndef.headerFilter = converter.to;
                if ('editor' in converter) {
                    columndef.editor = converter.editor;
                }
            }
            return columndef;
        }
    );
};

const MOODLE_FILTER_CONVERTER = {
    'input' : {
        to: 'string_filter'
    },
    'like': {
        to: 'numeric_comparison_filter',
        transformer:(args) => {
            return [
                JSON.stringify({
                direction : '=',
                value : args,
            })];
        }
    }
};
const rowQuery = (tableHandler, tableUniqueid, pageSize, params)  => {
    const args = {
        handler: tableHandler,
        uniqueid: tableUniqueid,
        sortdata: (typeof params.sorters === "undefined") ? [] : params.sorters.map(
            (e) => {
                return {'sortby': e.field, 'sortorder': e.dir.toUpperCase()};
            }
        ),
        filters: (typeof params.filters === "undefined") ? [] : params.filters.map(
            (e) => {
                let filter = {
                    'name': e.field, 'type': e.type ,'jointype': 1, 'values': e.value
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
        ),
        jointype: 1,
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
    if (typeof window.moment == "undefined") {
        window.moment = moment;
    }
    const tableelement = $("#" + tabulatorelementid);

    const placeHolderMessage = await getString('table:nodata', 'local_cltools');
    const columns = await Promise.race(ajaxCall(
        [{
            methodname: 'cltools_dynamic_table_get_columns',
            args: {
                handler: tableelement.data('tableHandler'),
                uniqueid: tableelement.data('tableUniqueid'),
            }
        }])).catch(Notification.exception);
    new Tabulator("#" + tabulatorelementid, {
        ajaxRequestFunc: function (url, config, params) {
            const tableHandler = tableelement.data('tableHandler');
            const tableUniqueid = tableelement.data('tableUniqueid');
            const pageSize = this.table.getPageSize();
            return rowQuery(tableHandler, tableUniqueid, pageSize, params);
        },
        ajaxURL: true, // If not set the RequestFunct will never be called.
        pagination: "remote",
        paginationSize: tableelement.data('table-pagesize'),
        ajaxFiltering: true,
        ajaxSorting: true,
        paginationDataReceived: {
            "last_page": "pagescount", // Change last_page parameter name to "pagescount".
        },
        ajaxResponse: ajaxResponseProcessor,
        columns: formatterFilterTransform(columns),
        layout: "fitColumns",
        placeholder: placeHolderMessage
    });

};
