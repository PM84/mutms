// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Provides support for general form autocomplete via ajax.
 *
 * @module      tool_mulib/form_autocomplete_ajax
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Load the list of items via WS.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {String} query The query string.
 * @param {Function} callback A callback function receiving an array of results.
 * @param {Function} failure A function to call in case of failure, receiving the error message.
 */
export async function transport(selector, query, callback, failure) {

    const wsmethod = document.querySelector(selector).getAttribute('data-ws-method');
    let wsarguments = document.querySelector(selector).getAttribute('data-ws-args');

    wsarguments = JSON.parse(wsarguments);
    if (Array.isArray(wsarguments)) {
        wsarguments = {};
    }

    if (typeof query !== 'string') {
        wsarguments.query = '';
    } else {
        wsarguments.query = query;
    }

    const request = {
        methodname: wsmethod,
        args: wsarguments
    };

    try {
        const response = await Ajax.call([request])[0];

        if (response.notice !== null) {
            callback(response.notice);
        } else {
            callback(response.list);
        }
    } catch (e) {
        failure(e);
    }
}

/**
 * Process the results for auto complete elements.
 *
 * @param {String} selector The selector of the auto complete element.
 * @param {Array} results An array or results returned by {@see transport()}.
 * @return {Array} New array of the selector options.
 */
export function processResults(selector, results) {
    return results;
}
