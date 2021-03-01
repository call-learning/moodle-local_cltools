import Tabulator from 'local_cltools/local/table/tabulator-lazy';
import $ from 'jquery';
import {call as ajaxCall} from 'core/ajax';

export const init = (tabulatorelementid) => {
    const tableelement = $("#" + tabulatorelementid);
    /*eslint no-unused-vars: ["error", {"args": "after-used"}]*/
    const rowQuery = function (url, config, params) {
        const args = {
            handler: tableelement.data('tableHandler'),
            uniqueid: tableelement.data('tableUniqueid'),
            sortdata: (typeof params.sorters === "undefined") ? [] : params.sorters.map(
                (e) => {
                    return {'sortby': e.name, 'sortorder': 'ASC'};
                }
            ),
            filters: (typeof params.filters === "undefined") ? [] : params.filters.map(
                (e) => {
                    return {'name': e.name, 'jointype': 1, 'values': e.values};
                }
            ),
            jointype: 1,
            pagenumber: params.page,
            pagesize: this.table.getPageSize(),
            hiddencolumns: [],
            resetpreferences: false,
            firstinitial: "A",
            lastinitial: "Z"
        };
        const promise = ajaxCall(
            [{
                methodname: 'cltools_dynamic_table_get_rows',
                args: args
            }]
        );
        return promise[0];
    };
    const ajaxResponseProcessor = function (url, params, response ) {
        response.data = response.data.map(
            (rowstring) => JSON.parse(rowstring)
        );
        return response;
    };
    const promise = ajaxCall(
        [{
            methodname: 'cltools_dynamic_table_get_columns',
            args: {
                handler: tableelement.data('tableHandler'),
                uniqueid: tableelement.data('tableUniqueid'),
            }
        }]
    );
    promise[0].then(
        (data) => {
            new Tabulator("#" + tabulatorelementid, {
                ajaxRequestFunc: rowQuery,
                ajaxURL: true, // If not set the RequestFunct will never be called.
                pagination: "remote",
                paginationSize: tableelement.data('table-pagesize'),
                ajaxFiltering: true,
                ajaxSorting: true,
                paginationDataReceived:{
                    "last_page":"pagescount", //change last_page parameter name to "pagescount"
                },
                ajaxResponse: ajaxResponseProcessor,
                columns: data
            });
        }
    ).fail(Notification.exception);
};