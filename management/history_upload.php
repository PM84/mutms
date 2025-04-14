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
 * Uploads user history for certifications.
 *
 * @package    tool_mucertify
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_mucertify\local\management;
use tool_mucertify\local\source\manual;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');

$certificationid = required_param('certificationid', PARAM_INT);
$draftitemid = optional_param('csvfile', null, PARAM_INT);

require_login();

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $certificationid, 'archived' => 0], '*', MUST_EXIST);
$source = $DB->get_record('tool_mucertify_source', ['certificationid' => $certification->id, 'type' => 'manual']);
$context = context::instance_by_id($certification->contextid);
require_capability('tool/mucertify:admin', $context);

$currenturl = new moodle_url('/admin/tool/mucertify/management/history_upload.php', ['certification' => $certificationid]);
$returnurl = new moodle_url('/admin/tool/mucertify/management/certification_users.php', ['id' => $certification->id]);

management::setup_certification_page($currenturl, $context, $certification, 'certification_users');

$filedata = null;
if ($draftitemid && confirm_sesskey()) {
    $filedata = \tool_mucertify\local\util::get_uploaded_data($draftitemid);
}

if (!$filedata) {
    $form = new \tool_mucertify\local\form\history_upload_file(null, [
        'certification' => $certification, 'context' => $context]);
} else {
    $form = new \tool_mucertify\local\form\history_upload_options(null, [
        'certification' => $certification, 'source' => $source,
        'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if ($filedata && $form instanceof \tool_mucertify\local\form\history_upload_options) {
        $result = \tool_mucertify\local\period::process_history_upload($data, $filedata);

        if ($result['assigned']) {
            $message = get_string('history_upload_result_assigned', 'tool_mucertify', $result['assigned']);
            \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($result['periods']) {
            $message = get_string('history_upload_result_periods', 'tool_mucertify', $result['periods']);
            \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($result['skipped']) {
            $message = get_string('history_upload_result_skipped', 'tool_mucertify', $result['skipped']);
            \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        }
        if ($result['errors']) {
            $message = get_string('history_upload_result_errors', 'tool_mucertify', $result['errors']);
            \core\notification::add($message, \core\output\notification::NOTIFY_WARNING);
        }

        $form->redirect_submitted($returnurl);
    }
    if (!$filedata && $form instanceof \tool_mucertify\local\form\history_upload_file) {
        $filedata = \tool_mucertify\local\util::get_uploaded_data($draftitemid);
        if ($filedata) {
            $form = new \tool_mucertify\local\form\history_upload_options(null, [
                'certification' => $certification, 'source' => $source,
                'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('history_upload', 'tool_mucertify'), 3);

echo $form->render();

echo $OUTPUT->footer();