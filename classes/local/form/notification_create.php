<?php
// This file is part of Additional tools library for Moodle™.

namespace tool_mulib\local\form;

/**
 * Notification create form.
 *
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_create extends \tool_mulib\local\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $component = $this->_customdata['component'];
        $instanceid = $this->_customdata['instanceid'];
        /** @var class-string<\tool_mulib\local\notification\manager> $manager */
        $manager = $this->_customdata['manager'];

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);
        $mform->setConstant('instanceid', $instanceid);

        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        $mform->setConstant('component', $component);

        $instance = $manager::get_instance_name($instanceid);
        $mform->addElement('static', 'staticinstance', get_string('notification_instance', 'tool_mulib'), $instance);

        $types = $manager::get_candidate_types($instanceid);
        $elements = [];
        foreach ($types as $type => $typename) {
            $elements[] = $mform->createElement('checkbox', $type, $typename);
        }
        $mform->addGroup($elements, 'types', get_string('notification_types', 'tool_mulib'), '<br />');

        $mform->addElement('advcheckbox', 'enabled', get_string('notification_enabled', 'tool_mulib'), ' ');
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons(true, get_string('notification_create', 'tool_mulib'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['types'])) {
            $errors['types'] = get_string('required');
        }

        return $errors;
    }
}
