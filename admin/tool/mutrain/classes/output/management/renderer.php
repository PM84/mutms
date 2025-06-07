<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutrain\output\management;

use stdClass, moodle_url;

/**
 * Frameworks management renderer.
 *
 * @package    tool_mutrain
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render framework.
     *
     * @param stdClass $framework
     * @return string
     */
    public function render_framework(stdClass $framework): string {
        $context = \context::instance_by_id($framework->contextid);

        $description = '';
        if ($framework->description) {
            $description = format_text($framework->description, $framework->descriptionformat, ['context' => $context]);
            $description = $this->output->box($description);
        }

        $details = [];

        $details[] = ['property' => get_string('framework_name', 'tool_mutrain'),
            'value' => format_string($framework->name)];
        if ($framework->idnumber === null) {
            $idnumber = get_string('notset', 'tool_mutrain');
        } else {
            $idnumber = s($framework->idnumber);
        }
        $details[] = ['property' => get_string('framework_idnumber', 'tool_mutrain'), 'value' => $idnumber];
        $details[] = ['property' => get_string('public', 'tool_mutrain'),
            'value' => ($framework->public ? get_string('yes') : get_string('no'))];
        $details[] = ['property' => get_string('context', 'role'),
            'value' => $context->get_context_name(false)];
        $details[] = ['property' => get_string('requiredtraining', 'tool_mutrain'),
            'value' => number_format($framework->requiredtraining, 0, '', ' ')];
        $details[] = ['property' => get_string('restrictedcompletion', 'tool_mutrain'),
            'value' => ($framework->restrictedcompletion ? get_string('yes') : get_string('no'))];
        $archived = $framework->archived ? get_string('yes') : get_string('no');
        if (has_capability('tool/mutrain:manageframeworks', $context)) {
            if ($framework->archived) {
                $url = new moodle_url('/admin/tool/mutrain/management/framework_restore.php', ['id' => $framework->id]);
                $action = new \tool_mulib\output\dialog_form\icon($url, get_string('framework_restore', 'tool_mutrain'), 'i/settings');
            } else {
                $url = new moodle_url('/admin/tool/mutrain/management/framework_archive.php', ['id' => $framework->id]);
                $action = new \tool_mulib\output\dialog_form\icon($url, get_string('framework_archive', 'tool_mutrain'), 'i/settings');
            }
            $action->set_dialog_size('');
            $archived .= $this->output->render($action);
        }
        $details[] = ['property' => get_string('archived', 'tool_mutrain'), 'value' => $archived];

        return $description . $this->output->render_from_template('tool_mulib/entity_details', ['details' => $details]);
    }
}
