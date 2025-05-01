<?php
// This file is part of Training plugin for Moodle™.
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

namespace tool_mutrain\local\form;

use tool_mutrain\external\form_field_add_fieldid;

/**
 * Add field to training framework.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_add extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $arguments = ['frameworkid' => $framework->id];
        form_field_add_fieldid::add_form_element(
            $mform, $arguments, 'fieldid', get_string('field', 'tool_mutrain'));
        $mform->addRule('fieldid', null, 'required', null, 'client');

        $this->add_action_buttons(true, get_string('field_add', 'tool_mutrain'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $framework = $this->_customdata['framework'];

        $arguments = ['frameworkid' => $framework->id];
        $error = form_field_add_fieldid::validate_form_value($arguments, $data['fieldid']);
        if ($error !== null) {
            $errors['fieldid'] = $error;
        }

        return $errors;
    }
}
