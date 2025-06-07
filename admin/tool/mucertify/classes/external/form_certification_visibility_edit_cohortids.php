<?php
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

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mucertify\external;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Certification visibility cohorts autocompletion.
 *
 * @package     tool_mucertify
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_certification_visibility_edit_cohortids extends \tool_mulib\external\form_autocomplete_field_cohort {
    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return true;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'certificationid' => new external_value(PARAM_INT, 'certification id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available cohorts.
     *
     * @param string $query The search request.
     * @param int $certificationid
     * @return array
     */
    public static function execute(string $query, int $certificationid): array {
        global $DB;

        ['query' => $query, 'certificationid' => $certificationid] = self::validate_parameters(
            self::execute_parameters(), ['query' => $query, 'certificationid' => $certificationid]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
        $context = \context::instance_by_id($certification->contextid);
        self::validate_context($context);
        require_capability('tool/mucertify:edit', $context);

        list($searchsql, $params) = self::get_cohort_search_query($query, 'ch');
        $tenantselect = "";
        if (\tool_mucertify\local\util::is_mutenancy_active()) {
            if ($context->tenantid) {
                $tenantselect = "AND (c.tenantid IS NULL OR c.tenantid = :tenantid)";
                $params['tenantid'] = $context->tenantid;
            }
        }

        $sql = "SELECT ch.id, ch.name, ch.contextid, ch.visible
                  FROM {cohort} ch
                  JOIN {context} c ON c.id = ch.contextid
                 WHERE $searchsql $tenantselect
              ORDER BY ch.name ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        return self::prepare_cohort_list($rs);
    }

    /**
     * Validate user can select cohort.
     *
     * @param int $cohortid
     * @param int $certificationid 0 means no certification yet
     * @return string|null null means ids ok, string is error
     */
    public static function validate_cohortid(int $cohortid, int $certificationid): ?string {
        global $DB;
        $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
        if (!$cohort) {
            return get_string('error');
        }
        $context = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$context) {
            return get_string('error');
        }
        if ($DB->record_exists('tool_mucertify_cohort', ['cohortid' => $cohort->id, 'certificationid' => $certificationid])) {
            // Existing cohorts are always fine.
            return null;
        }
        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);
        if (\tool_mucertify\local\util::is_mutenancy_active()) {
            $certificationcontext = \context::instance_by_id($certification->contextid);
            if ($context->tenantid && $certificationcontext->tenantid && $context->tenantid != $certificationcontext->tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        }
        if (!self::is_cohort_visible($cohort, $context)) {
            return get_string('error');
        }
        return null;
    }
}
