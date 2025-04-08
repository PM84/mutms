<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mulib\local\form;

use tool_mulib\local\notification\manager;

/**
 * Notification import confirmation form.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_import_confirmation extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $component = $this->_customdata['component'];
        $instanceid = $this->_customdata['instanceid'];
        $frominstance = $this->_customdata['frominstance'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        $mform->setConstant('component', $component);

        $mform->addElement('hidden', 'frominstance');
        $mform->setType('frominstance', PARAM_INT);
        $mform->setConstant('frominstance', $frominstance);

        $fromname = $manager::get_instance_name($instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_import_from', 'tool_mulib'), $fromname);

        $types = $manager::get_all_types();

        $notifications = $DB->get_records('tool_mulib_notification',
            ['instanceid' => $frominstance, 'component' => $component, 'enabled' => 1]);
        foreach ($notifications as $notification) {
            if (!isset($types[$notification->notificationtype])) {
                continue;
            }
            $classname = $types[$notification->notificationtype];
            $mform->addElement('advcheckbox', 'notificationid_'.$notification->id, $classname::get_name(), null,
                ['group' => 1]);
        }
        $this->add_checkbox_controller(1);

        $this->add_action_buttons(true, get_string('notification_import', 'tool_mulib'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
