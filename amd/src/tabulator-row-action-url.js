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
 * AMD module for a small util allowing to click and change url on a row click
 *
 * @module   local_cltools/table/tabulator-row-action-url.js
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';

export const init = (tableuniqueid, baseurl, parameters) => {
    $(document).on('tabulator-row-click', function (event, row, uniqueid) {
        if (uniqueid === tableuniqueid) {
            const data = row.getData();
            if (typeof (data.id) !== "undefined") {
                let url = new URL(baseurl);
                let searchParams = url.searchParams;
                baseurl = baseurl.replace(url.search, '');
                for (const [key, value] of Object.entries(parameters)) {
                    searchParams.set(key, data[value]);
                }
                let paramurl = searchParams.toString();
                window.location.href = `${baseurl}?${paramurl}`;
            }
        }
    });
};