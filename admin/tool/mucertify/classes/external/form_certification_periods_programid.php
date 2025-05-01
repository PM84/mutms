<?php
// This file is part of Certifications for Moodle™.
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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mucertify\external;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Provides list of program candidates for certification.
 *
 * @package     tool_mucertify
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_certification_periods_programid extends \tool_mulib\external\form_autocomplete_field {
    /** @var int max results */
    const MAX_RESULTS = 20;

    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
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
     * Finds candidate programs for given certification.
     *
     * @param string $query The search request.
     * @param int $certificationid The certification.
     * @return array
     */
    public static function execute(string $query, int $certificationid): array {
        global $DB;

        ['query' => $query, 'certificationid' => $certificationid] = self::validate_parameters(
            self::execute_parameters(), ['query' => $query, 'certificationid' => $certificationid]);

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($certification->contextid);
        self::validate_context($context);
        require_capability('tool/mucertify:edit', $context);

        list($searchsql, $params) = \tool_muprog\local\management::get_program_search_query(null, $query, 'p');

        $tenantselect = '';
        if (\tool_mucertify\local\util::is_mutenancy_active()) {
            $certificationtenantid = $DB->get_field('context', 'tenantid', ['id' => $context->id]);
            if ($certificationtenantid) {
                $tenantselect = "AND (ctx.tenantid = :tenantid OR ctx.tenantid IS NULL)";
                $params['tenantid'] = $certificationtenantid;
            }
        }

        $sqlquery = <<<SQL
            SELECT p.*
              FROM {tool_muprog_program} p
              JOIN {tool_muprog_source} s ON s.programid = p.id and s.type = 'mucertify'
              JOIN {context} ctx ON ctx.id = p.contextid
             WHERE p.archived = 0 AND $searchsql
                   $tenantselect
          ORDER BY p.fullname ASC
SQL;

        $rs = $DB->get_recordset_sql($sqlquery, $params);

        $count = 0;
        $list = [];
        $notice = null;

        foreach ($rs as $program) {
            $context = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:addtocertifications', $context)) {
                continue;
            }
            $count++;
            if ($count > self::MAX_RESULTS) {
                $notice = get_string('toomanyrecords', 'tool_mulib', self::MAX_RESULTS);
                break;
            }

            $list[] = [
                'value' => $program->id,
                'label' => format_string($program->fullname),
            ];
        }
        $rs->close();

        return [
            'notice' => $notice,
            'list' => $list,
        ];
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            global $DB;

            if (!$value) {
                return '';
            }

            $certification = $DB->get_record('tool_mucertify_certification', ['id' => $arguments['certificationid']], '*', MUST_EXIST);
            $context = \context::instance_by_id($certification->contextid);

            $error = '';
            if (self::validate_form_value($arguments, $value, $context) !== null) {
                $error = ' (' . get_string('error') .')';
            }

            $program = $DB->get_record('tool_muprog_program', ['id' => $value]);
            if ($program) {
                return format_string($program->fullname) . $error;
            } else {
                return trim($error);
            }
        };
    }

    /**
     * Is valid value?
     *
     * @param array $arguments
     * @param mixed $value
     * @return string|null error message, NULL means value is ok
     */
    public static function validate_form_value(array $arguments, $value): ?string {
        global $DB;

        if (!$value) {
            return null;
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $value]);
        if (!$program) {
            return get_string('error');
        }

        $certification = $DB->get_record('tool_mucertify_certification', ['id' => $arguments['certificationid']], '*', MUST_EXIST);
        if ($program->id == $certification->programid1 || $program->id == $certification->programid2) {
            // Current value is always ok.
            return null;
        }

        return null;
    }
}
