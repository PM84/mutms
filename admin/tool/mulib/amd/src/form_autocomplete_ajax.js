// This file is part of Additional tools library for Moodle™.

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
