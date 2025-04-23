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
 * Certification browsing for learners.
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
/** @var stdClass $USER */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_url(new moodle_url('/admin/tool/mucertify/catalogue/certification.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());

require_login();
require_capability('tool/mucertify:viewcatalogue', context_system::instance());

if (!\tool_mucertify\local\util::is_mucertify_active()) {
    redirect(new moodle_url('/'));
}

$certification = $DB->get_record('tool_mucertify_certification', ['id' => $id]);
if (!$certification || $certification->archived) {
    if ($certification) {
        $context = context::instance_by_id($certification->contextid);
    } else {
        $context = context_system::instance();
    }
    if (has_capability('tool/mucertify:view', $context)) {
        if ($certification) {
            redirect(new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
        } else {
            redirect(new moodle_url('/admin/tool/mucertify/management/index.php'));
        }
    } else {
        redirect(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
    }
}
$certificationcontext = context::instance_by_id($certification->contextid);

$assignment = $DB->get_record('tool_mucertify_assignment', ['certificationid' => $certification->id, 'userid' => $USER->id]);
if ($assignment && !$assignment->archived) {
    redirect(new moodle_url('/admin/tool/mucertify/my/certification.php', ['id' => $id]));
}

if (!\tool_mucertify\local\catalogue::is_certification_visible($certification)) {
    if (has_capability('tool/mucertify:view', $certificationcontext)) {
        redirect(new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]));
    } else {
        redirect(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
    }
}

if (has_capability('tool/mucertify:view', $certificationcontext)) {
    $manageurl = new moodle_url('/admin/tool/mucertify/management/certification.php', ['id' => $certification->id]);
    $button = html_writer::link($manageurl, get_string('management', 'tool_mucertify'), ['class' => 'btn btn-secondary']);
    $PAGE->set_button($button . $PAGE->button);
}

/** @var \tool_mucertify\output\catalogue\renderer $catalogueoutput */
$catalogueoutput = $PAGE->get_renderer('tool_mucertify', 'catalogue');

$PAGE->set_heading(get_string('catalogue', 'tool_mucertify'));
$PAGE->navigation->override_active_url(new moodle_url('/admin/tool/mucertify/catalogue/index.php'));
$PAGE->set_title(get_string('catalogue', 'tool_mucertify'));
$PAGE->navbar->add(format_string($certification->fullname));

echo $OUTPUT->header();

echo $catalogueoutput->render_certification($certification);

echo $OUTPUT->footer();
