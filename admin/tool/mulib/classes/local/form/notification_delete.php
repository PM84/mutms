<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mulib\local\form;

/**
 * Notification delete form.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_delete extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $notification = $this->_customdata['notification'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setConstant('id', $notification->id);

        $instance = $manager::get_instance_name($notification->instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'tool_mulib'), $instance);

        $types = $manager::get_all_types();
        $type = $types[$notification->notificationtype] ?? null;
        if ($type) {
            $type = $type::get_name();
        } else {
            $type = get_string('error');
        }
        $mform->addElement('static', 'staticnotificationtype', get_string('notification_type', 'tool_mulib'), $type);

        $warning = '<em>' . get_string('notification_delete_confirm', 'tool_mulib') . '</em>';
        $mform->addElement('static', 'staticwarning', '', $warning);

        $this->add_action_buttons(true, get_string('notification_delete', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
