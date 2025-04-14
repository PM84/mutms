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
 * Certifications management interface.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mucertify\local\management;
use tool_mulib\output\action_menu\dropdown;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$contextid = optional_param('contextid', 0, PARAM_INT);

if ($contextid) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
} else {
    $context = context_system::instance();
}

require_login();
require_capability('tool/mucertify:view', $context);

if ($context->contextlevel == CONTEXT_SYSTEM) {
    $category = null;
} else if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', ['id' => $context->instanceid], '*', MUST_EXIST);
} else {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $contextid]);

management::setup_index_page($currenturl, $context);

$buttons = [];
$dropdown = new dropdown(get_string('extra_menu_management_index', 'tool_muprog'));

if (has_capability('tool/mucertify:edit', $context)) {
    $addurl = new moodle_url('/admin/tool/mucertify/management/certification_create.php', ['contextid' => $context->id]);
    $addbutton = new tool_mulib\output\dialog_form\button($addurl, get_string('certification_create', 'tool_mucertify'));
    $addbutton->set_after_submit($addbutton::AFTER_SUBMIT_REDIRECT);
    $buttons[] = $OUTPUT->render($addbutton);
}

if ($buttons || $dropdown->has_items()) {
    $action = '';
    if ($buttons) {
        $action .= implode($buttons);
    }
    if ($dropdown->has_items()) {
        $action .= $OUTPUT->render($dropdown);
    }
    $PAGE->add_header_action($action);
}

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_mucertify\reportbuilder\local\systemreports\certifications::class,
    $context);
echo $report->output();

echo $OUTPUT->footer();
