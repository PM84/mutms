<?php
// This file is part of Additional tools library for Moodle™.
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
 * Notification details page.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
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

if (!$manager::can_view($notification->instanceid)) {
    redirect('/');
}

$context = $manager::get_instance_context($notification->instanceid);

$PAGE->set_context($context);
$PAGE->set_url('/admin/tool/mulib/notification/view.php', ['id' => $notification->id]);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(get_string('notification', 'tool_mulib'));
$PAGE->set_title(get_string('notification', 'tool_mulib'));

/** @var class-string<\tool_mulib\local\notification\notificationtype> $classname */
$classname = $manager::get_classname($notification->notificationtype);
if (!$classname || !class_exists($classname)) {
    throw new invalid_parameter_exception('Unknown notification type');
}

$manager::setup_view_page($notification);

$details = [];

$name = $classname::get_name();
$details[] = ['property' => get_string('notification', 'tool_mulib'), 'value' => $name];
$instancename = $manager::get_instance_name($notification->instanceid);
$manageurl = $manager::get_instance_management_url($notification->instanceid);
if ($manageurl) {
    $instancename = html_writer::link($manageurl, $instancename);
}
$details[] = ['property' => get_string('notification_instance', 'tool_mulib'), 'value' => $instancename];
$description = $classname::get_description();
$enabled = $notification->enabled ? get_string('yes') : get_string('no');
$details[] = ['property' => get_string('notification_enabled', 'tool_mulib'), 'value' => $enabled ];
$details[] = ['property' => get_string('description'), 'value' => $description ];
$custom = $notification->custom ? get_string('yes') : get_string('no');
$details[] = ['property' => get_string('notification_custom', 'tool_mulib'), 'value' => $custom ];
$a = [];
$subject = $classname::get_subject($notification, $a);
$details[] = ['property' => get_string('notification_subject', 'tool_mulib'), 'value' => $subject ];
$body = $classname::get_body($notification, $a);
$details[] = ['property' => get_string('notification_body', 'tool_mulib'), 'value' => $body ];

echo $OUTPUT->render_from_template('tool_mulib/entity_details', ['details' => $details]);

$buttons = [];

if ($manager::can_manage($notification->instanceid)) {
    $url = new \moodle_url('/admin/tool/mulib/notification/delete.php', ['id' => $notification->id]);
    $button = new \tool_mulib\output\dialog_form\button($url, get_string('notification_delete', 'tool_mulib'));
    $button->set_after_submit($button::AFTER_SUBMIT_REDIRECT);
    $buttons[] = $OUTPUT->render($button);
    if ($classname) {
        $url = new \moodle_url('/admin/tool/mulib/notification/update.php', ['id' => $notification->id]);
        $button = new \tool_mulib\output\dialog_form\button($url, get_string('notification_update', 'tool_mulib'));
        $buttons[] = $OUTPUT->render($button);
    }
}
if ($manageurl) {
    $button = new single_button($manageurl, get_string('back'), 'get');
    $buttons[] = ' ' . $OUTPUT->render($button);
}

if ($buttons) {
    echo $OUTPUT->box(implode('', $buttons), 'buttons');
}
echo $OUTPUT->footer();
