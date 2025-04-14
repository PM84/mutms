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
use tool_mucertify\local\assignment;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$assignment = $DB->get_record('tool_mucertify_assignment', ['id' => $id], '*', MUST_EXIST);
$certification = $DB->get_record('tool_mucertify_certification', ['id' => $assignment->certificationid], '*', MUST_EXIST);
$source = $DB->get_record('tool_mucertify_source', ['id' => $assignment->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:assign', $context);

$returnurl = new moodle_url('/admin/tool/mucertify/management/certification_users.php', ['id' => $certification->id]);

$user = $DB->get_record('user', ['id' => $assignment->userid], '*', MUST_EXIST);

/** @var \tool_mucertify\local\source\base $coursceclass */
$coursceclass = assignment::get_source_classes()[$source->type];
if (!$coursceclass::assignment_delete_supported($certification, $source, $assignment)) {
    redirect($returnurl);
}

$currenturl = new moodle_url('/admin/tool/mucertify/management/user_assignment_delete.php', ['id' => $assignment->id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_users');

$form = new \tool_mucertify\local\form\user_assignment_delete(null, ['assignment' => $assignment, 'user' => $user, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $coursceclass::unassign_user($certification, $source, $assignment);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(fullname($user), 3);

echo $form->render();

echo $OUTPUT->footer();
