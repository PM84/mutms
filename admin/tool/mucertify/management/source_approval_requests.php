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

/**
 * certification management interface.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_mucertify\local\management;

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:view', $context);

$currenturl = new moodle_url('/admin/tool/mucertify/management/source_approval_requests.php', ['id' => $certification->id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_approval_requests');

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mucertify\reportbuilder\local\systemreports\requests::class,
    $context, parameters:['certificationid' => $certification->id]);
echo $report->output();

echo $OUTPUT->footer();
