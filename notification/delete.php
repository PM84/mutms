<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * delete existing notification.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\local\notification\util;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$notification = $DB->get_record('tool_mulib_notification', ['id' => $id], '*', MUST_EXIST);

/** @var class-string<\tool_mulib\local\notification\manager> $manager */
$manager = \tool_mulib\local\notification\util::get_manager_classname($notification->component);
if (!$manager) {
    throw new invalid_parameter_exception('Invalid notification component');
}

$returnurl = $manager::get_instance_management_url($notification->instanceid);
if (!$manager::can_manage($notification->instanceid)) {
    redirect($returnurl);
}

$context = $manager::get_instance_context($notification->instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/mulib/notification/delete.php', ['id' => $notification->id]);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('notification_delete', 'tool_mulib'));
$PAGE->set_title(get_string('notification_delete', 'tool_mulib'));

$form = new \tool_mulib\local\form\notification_delete(null, ['notification' => $notification, 'manager' => $manager]);
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    util::notification_delete($data->id);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('notification_delete', 'tool_mulib'));
echo $form->render();
echo $OUTPUT->footer();
