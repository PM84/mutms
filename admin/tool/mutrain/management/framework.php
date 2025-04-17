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

/**
 * Training frameworks.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\output\dropdown;
use tool_mutrain\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');
require_once("$CFG->libdir/adminlib.php");

$id = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('tool/mutrain:viewframeworks', $context);

$pageurl = new \moodle_url('/admin/tool/mutrain/management/framework.php', ['id' => $framework->id]);

management::setup_framework_page($pageurl, $context, $framework);

/** @var \tool_mutrain\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mutrain', 'management');

$buttons = [];

$dropdown = new dropdown(get_string('extra_menu_management_framework', 'tool_mutrain'));
if (has_capability('tool/mutrain:manageframeworks', $context)) {
    if (\tool_mutrain\local\framework::is_deletable($framework->id)) {
        $url = new moodle_url('/admin/tool/mutrain/management/framework_delete.php', ['id' => $framework->id]);
        $link = new tool_mulib\output\dialog_form\link($url, get_string('framework_delete', 'tool_mutrain'));
        $link->set_after_submit($link::AFTER_SUBMIT_REDIRECT);
        $dropdown->add_dialog_form($link);
    }
    $url = new \moodle_url('/admin/tool/mutrain/management/framework_update.php', ['id' => $framework->id]);
    $button = new \tool_mulib\output\dialog_form\button($url, get_string('framework_update', 'tool_mutrain'));
    $buttons[] = $OUTPUT->render($button);
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

if ($framework->description) {
    $description = format_text($framework->description, $framework->descriptionformat, ['context' => $context]);
    echo $OUTPUT->box($description);
}

echo $managementoutput->render_framework($framework);

echo $OUTPUT->heading(get_string('fields', 'tool_mutrain'), 4);

$table = new \tool_mutrain\table\fields($pageurl, $framework);
$table->out($table->pagesize, false);

if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
    $url = new \moodle_url('/admin/tool/mutrain/management/field_add.php', ['frameworkid' => $framework->id]);
    $button = get_string('field_add', 'tool_mutrain');
    $button = new \tool_mulib\output\dialog_form\button($url, $button);
    $addbutton = $OUTPUT->render($button);
    echo '<br /><div class="buttons">' . $addbutton . '</div>';
}

echo $OUTPUT->footer();
