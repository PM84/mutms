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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mucertify\output\management;

use tool_mucertify\local\notification_manager;
use tool_mucertify\local\assignment;
use tool_mucertify\local\management;
use tool_mucertify\local\util;
use tool_mucertify\local\certification;
use tool_mucertify\local\period;
use stdClass, moodle_url, html_writer;

/**
 * Certification management renderer.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render general certification info.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_general(stdClass $certification): string {
        global $CFG;

        $context = \context::instance_by_id($certification->contextid);

        $result = '';

        $presentation = (array)json_decode($certification->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_mucertify/image/' . $certification->id . '/'. $presentation['image'], false);
            $result .= '<div class="float-end certificationimage">' . html_writer::img($imageurl, '') . '</div>';
        }
        $result .= '<dl class="row">';
        $result .= '<dt class="col-3">' . get_string('certificationname', 'tool_mucertify') . '</dt><dd class="col-9">'
            . format_string($certification->fullname) . '</dd>';
        $result .= '<dt class="col-3">' . get_string('certificationidnumber', 'tool_mucertify') . '</dt><dd class="col-9">'
            . s($certification->idnumber) . '</dd>';
        $result .= '<dt class="col-3">' . get_string('category') . '</dt><dd class="col-9">'
            . html_writer::link(new moodle_url('/admin/tool/mucertify/management/index.php',
                ['contextid' => $context->id]), $context->get_context_name(false)) . '</dd>';
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_mucertify', 'certification', $certification->id);
            if ($tags) {
                $result .= '<dt class="col-3">' . get_string('tags') . '</dt><dd class="col-9">'
                    . $this->output->tag_list($tags, '', 'certification-tags') . '</dd>';
            }
        }
        $description = file_rewrite_pluginfile_urls($certification->description, 'pluginfile.php', $context->id, 'tool_mucertify', 'description', $certification->id);
        $description = format_text($description, $certification->descriptionformat, ['context' => $context]);
        if (trim($description) === '') {
            $description = '&nbsp;';
        }
        $result .= '<dt class="col-3">' . get_string('description') . '</dt><dd class="col-9">' . $description . '</dd>';

        $archived = $certification->archived ? get_string('yes') : get_string('no');
        if (has_capability('tool/mucertify:edit', $context)) {
            if ($certification->archived) {
                $url = new moodle_url('/admin/tool/mucertify/management/certification_restore.php', ['id' => $certification->id]);
                $action = new \tool_mulib\output\dialog_form\icon($url, get_string('certification_restore', 'tool_mucertify'), 'i/settings');
            } else {
                $url = new moodle_url('/admin/tool/mucertify/management/certification_archive.php', ['id' => $certification->id]);
                $action = new \tool_mulib\output\dialog_form\icon($url, get_string('certification_archive', 'tool_mucertify'), 'i/settings');
            }
            $action->set_dialog_size('');
            $archived .= $this->output->render($action);
        }
        $result .= '<dt class="col-3">' . get_string('archived', 'tool_mucertify') . '</dt><dd class="col-9">' . $archived . '</dd>';

        /** @var \tool_mucertify\output\customfield\renderer $customfieldoutput */
        $customfieldoutput = $this->page->get_renderer('tool_mucertify', 'customfield');
        $result .= $customfieldoutput->render_customfields($certification->id);
        $result .= '</dl>';

        return $result;
    }

    /**
     * Render certification visibility.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_visibility(stdClass $certification): string {
        $result = '';

        $result .= '<dl class="row">';
        $result .= '<dt class="col-3">' . get_string('public', 'tool_mucertify') . '</dt><dd class="col-9">'
            . ($certification->public ? get_string('yes') : get_string('no')) . '</dd>';
        $result .= '<dt class="col-3">' . get_string('cohorts', 'tool_mucertify') . '</dt><dd class="col-9">';
        $cohorts = management::fetch_current_cohorts_menu($certification->id);
        if ($cohorts) {
            $result .= implode(', ', array_map('format_string', $cohorts));
        } else {
            $result .= '-';
        }
        $result .= '</dd>';
        $result .= '</dl>';

        return $result;
    }

    /**
     * Render first period settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_settings1(stdClass $certification): string {
        global $DB;

        $settings = certification::get_periods_settings($certification);

        $result = '';
        $result .= '<dl class="row">';

        if ($settings->programid1) {
            $program1 = $DB->get_record('tool_muprog_program', ['id' => $settings->programid1]);
            if ($program1) {
                $context = \context::instance_by_id($program1->contextid, IGNORE_MISSING);
                $program1str = format_string($program1->fullname);
                if ($context && has_capability('tool/muprog:view', $context)) {
                    $url = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program1->id]);
                    $program1str = html_writer::link($url, $program1str);
                }
                if (!$DB->record_exists('tool_muprog_source', ['programid' => $program1->id, 'type' => 'mucertify'])) {
                    $program1str = '<span class="badge badge-danger">' . $program1str . '</span>';
                }
            } else {
                $program1str = '<span class="badge badge-danger">' . get_string('error') . '</span>';
            }
        } else {
            $program1str = '<span class="badge badge-danger">' . get_string('notset', 'tool_mucertify') . '</span>';
        }
        $result .= '<dt class="col-3">' . get_string('program', 'tool_muprog') . '</dt><dd class="col-9">'
            . $program1str . '</dd>';

        if ($settings->due1) {
            $due = util::format_duration($settings->due1);
        } else {
            $due = get_string('notset', 'tool_mucertify');
        }
        $result .= '<dt class="col-3">' . get_string('windowduedate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $due . '</dd>';

        $since = certification::get_valid_options();
        $result .= '<dt class="col-3">' . get_string('fromdate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $since[$settings->valid1] . '</dd>';

        $since = certification::get_windowend_options();
        if ($settings->windowend1['since'] === certification::SINCE_NEVER) {
            $windowend = $since[$settings->windowend1['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->windowend1['delay']);
            $a->after = $since[$settings->windowend1['since']];
            $windowend = get_string('delayafter', 'tool_mucertify', $a);
        }
        $result .= '<dt class="col-3">' . get_string('windowenddate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $windowend . '</dd>';

        $since = certification::get_expiration_options();
        if ($settings->expiration1['since'] === certification::SINCE_NEVER) {
            $expiration = $since[$settings->expiration1['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->expiration1['delay']);
            $a->after = $since[$settings->expiration1['since']];
            $expiration = get_string('delayafter', 'tool_mucertify', $a);
        }
        $result .= '<dt class="col-3">' . get_string('untildate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $expiration . '</dd>';

        $resettypes = certification::get_resettype_options();
        $result .= '<dt class="col-3">' . get_string('resettype1', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $resettypes[$settings->resettype1] . '</dd>';

        if ($settings->recertify === null) {
            $recertifystr = get_string('no');
        } else {
            $a = new stdClass();
            $a->delay = util::format_duration($settings->recertify);
            $a->before = get_string('untildate', 'tool_mucertify');
            $recertifystr = get_string('delaybefore', 'tool_mucertify', $a);
        }
        $result .= '<dt class="col-3">' . get_string('recertify', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $recertifystr . '</dd>';

        $result .= '</dl>';
        return $result;
    }

    /**
     * Render recertification period settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_settings2(stdClass $certification): string {
        global $DB;

        $settings = certification::get_periods_settings($certification);

        $result = '';
        $result .= '<dl class="row">';

        if ($settings->programid2) {
            $program2 = $DB->get_record('tool_muprog_program', ['id' => $settings->programid2]);
            if ($program2) {
                $context = \context::instance_by_id($program2->contextid, IGNORE_MISSING);
                $program2str = format_string($program2->fullname);
                if ($context && has_capability('tool/muprog:view', $context)) {
                    $url = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program2->id]);
                    $program2str = html_writer::link($url, $program2str);
                }
                if (!$DB->record_exists('tool_muprog_source', ['programid' => $program2->id, 'type' => 'mucertify'])) {
                    $program2str = '<span class="badge badge-danger">' . $program2str . '</span>';
                }
            } else {
                $program2str = '<span class="badge badge-danger">' . get_string('error') . '</span>';
            }
        } else {
            $program2str = '<span class="badge badge-danger">' . get_string('notset', 'tool_mucertify') . '</span>';
        }
        $result .= '<dt class="col-3">' . get_string('program', 'tool_muprog') . '</dt><dd class="col-9">'
            . $program2str . '</dd>';

        if ($settings->grace2) {
            $grace = util::format_duration($settings->grace2);
        } else {
            $grace = get_string('notset', 'tool_mucertify');
        }
        $result .= '<dt class="col-3">' . get_string('graceperiod', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $grace . '</dd>';

        $resettypes = certification::get_resettype_options();
        $result .= '<dt class="col-3">' . get_string('resettype2', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $resettypes[$settings->resettype2] . '</dd>';

        $since = certification::get_valid_options();
        $result .= '<dt class="col-3">' . get_string('fromdate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $since[$settings->valid2] . '</dd>';

        $since = certification::get_windowend_options();
        if ($settings->windowend2['since'] === certification::SINCE_NEVER) {
            $windowend = $since[$settings->windowend2['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->windowend2['delay']);
            $a->after = $since[$settings->windowend2['since']];
            $windowend = get_string('delayafter', 'tool_mucertify', $a);
        }
        $result .= '<dt class="col-3">' . get_string('windowenddate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $windowend . '</dd>';

        $since = certification::get_expiration_options();
        if ($settings->expiration2['since'] === certification::SINCE_NEVER) {
            $expiration = $since[$settings->expiration2['since']];
        } else {
            $a = new stdClass();
            $a->delay = util::format_interval($settings->expiration2['delay']);
            $a->after = $since[$settings->expiration2['since']];
            $expiration = get_string('delayafter', 'tool_mucertify', $a);
        }
        $result .= '<dt class="col-3">' . get_string('untildate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $expiration . '</dd>';

        $result .= '</dl>';
        return $result;
    }

    /**
     * Render certificate settings.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_certificate(stdClass $certification): string {
        global $DB;

        $result = '';
        $result .= '<dl class="row">';
        $result .= '<dt class="col-3">' . get_string('certificatetemplate', 'tool_certificate') . '</dt><dd class="col-9">';
        if ($certification->templateid) {
            $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
            if (!$template) {
                $result .= get_string('error');
            } else {
                $name = format_string($template->name);
                $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
                if ($template) {
                    $templatecontext = \context::instance_by_id($template->contextid);
                    if (has_capability('tool/certificate:viewallcertificates', $templatecontext)) {
                        $url = new moodle_url('/admin/tool/certificate/certificates.php', ['templateid' => $template->id]);
                        $name = html_writer::link($url, $name);
                    }
                }
                $result .= $name;
            }
        } else {
            $result .= get_string('notset', 'tool_muprog');
        }
        $result .= '</dd>';
        $result .= '</dl>';

        return $result;
    }

    /**
     * Render assignment.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @param bool $subpage
     * @return string
     */
    public function render_user_assignment(stdClass $certification, stdClass $assignment, bool $subpage = false): string {
        global $DB;

        $sourceclasses = assignment::get_source_classes();
        $sourcenames = assignment::get_source_names();
        $context = \context::instance_by_id($certification->contextid);
        $source = $DB->get_record('tool_mucertify_source', ['id' => $assignment->sourceid], '*', MUST_EXIST);
        /** @var \tool_mucertify\local\source\base $sourceclass */
        $sourceclass = $sourceclasses[$source->type];

        $buttons = [];
        $backheading = '';
        if ($subpage) {
            $backurl = new moodle_url('/admin/tool/mucertify/management/user_assignment.php', ['id' => $assignment->id]);
            $backheading = $this->output->heading(get_string('periods', 'tool_mucertify'), 3, ['h4']);
            $backheading = \html_writer::link($backurl, $backheading);
        } else {
            if (has_capability('tool/mucertify:admin', $context)) {
                if ($sourceclass::assignment_edit_supported($certification, $source, $assignment)) {
                    $updateurl = new moodle_url('/admin/tool/mucertify/management/user_assignment_edit.php', ['id' => $assignment->id]);
                    $updatebutton = new \tool_mulib\output\dialog_form\button($updateurl, get_string('updateassignment', 'tool_mucertify'));
                    $buttons[] = $this->output->render($updatebutton);
                }
            }
            if (has_capability('tool/mucertify:assign', $context)) {
                if ($sourceclass::assignment_delete_supported($certification, $source, $assignment)) {
                    $deleteurl = new moodle_url('/admin/tool/mucertify/management/user_assignment_delete.php', ['id' => $assignment->id]);
                    $deletebutton = new \tool_mulib\output\dialog_form\button($deleteurl, get_string('deleteassignment', 'tool_mucertify'));
                    $deletebutton->set_after_submit($deletebutton::AFTER_SUBMIT_REDIRECT);
                    $buttons[] = $this->output->render($deletebutton);
                }
            }
            if (!$certification->archived && !$assignment->archived && has_capability('tool/mucertify:admin', $context)) {
                $addurl = new moodle_url('/admin/tool/mucertify/management/period_create.php', ['assignmentid' => $assignment->id]);
                $addbutton = new \tool_mulib\output\dialog_form\button($addurl, get_string('period_create', 'tool_mucertify'));
                $buttons[] = $this->output->render($addbutton);
            }
        }

        $result = '';

        $result .= '<dl class="row">';
        $result .= '<dt class="col-3">' . get_string('certificationstatus', 'tool_mucertify') . '</dt><dd class="col-9">'
            . assignment::get_status_html($certification, $assignment) . '</dd>';
        $result .= '<dt class="col-3">' . get_string('archived', 'tool_mucertify') . '</dt><dd class="col-9">'
            . (($certification->archived || $assignment->archived) ? get_string('yes') : get_string('no')) . '<br />';

        if ($certification->recertify && !$certification->archived && !$assignment->archived) {
            $stoprecertify = !$DB->record_exists('tool_mucertify_period', [
                'certificationid' => $assignment->certificationid,
                'userid' => $assignment->userid,
                'recertifiable' => 1,
            ]);
            $result .= '<dt class="col-3">' . get_string('stoprecertify', 'tool_mucertify') . '</dt><dd class="col-9">'
                . ($stoprecertify ? get_string('yes') : get_string('no')) . '<br />';
        }

        if ($assignment->timecertifiedtemp) {
            $result .= '<dt class="col-3">' . get_string('certifieduntiltemporary', 'tool_mucertify') . '</dt><dd class="col-9">'
                . userdate($assignment->timecertifiedtemp) . '</dd>';
        }
        $result .= '<dt class="col-3">' . get_string('source', 'tool_mucertify') . '</dt><dd class="col-9">'
            . $sourcenames[$source->type] . '</dd>';
        $result .= '</dl>';

        if ($buttons) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $buttons);
            $result .= '</div>';
        }

        $result .= $backheading;

        return $result;
    }

    /**
     * Render periods.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_periods(stdClass $certification, stdClass $assignment): string {
        $result = $this->output->heading(get_string('periods', 'tool_mucertify'), 3, ['h4']);

        $table = new \tool_mucertify\table\assignment_periods($certification, $assignment, $this->page->url);
        ob_start();
        $table->out($table->pagesize, false);
        $result .= ob_get_clean();

        return $result;
    }

    /**
     * Render period.
     *
     * @param stdClass $certification
     * @param stdClass|null $assignment
     * @param stdClass $period
     * @return string
     */
    public function render_user_period(stdClass $certification, ?stdClass $assignment, stdClass $period): string {
        global $DB;

        $context = \context::instance_by_id($certification->contextid);
        $strnotset = get_string('notset', 'tool_mucertify');

        $program = false;
        $programcontext = false;
        $allocation = false;
        if ($period->programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $period->programid]);
            $programcontext = \context::instance_by_id($program->contextid, IGNORE_MISSING);
        }
        if ($program && $period->allocationid) {
            $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $period->allocationid]);
        }

        $buttons = [];

        if (has_capability('tool/mucertify:admin', $context)) {
            $updateurl = new moodle_url('/admin/tool/mucertify/management/period_update.php', ['id' => $period->id]);
            $updatebutton = new \tool_mulib\output\dialog_form\button($updateurl, get_string('period_update', 'tool_mucertify'));
            $buttons[] = $this->output->render($updatebutton);

            if ($period->timerevoked) {
                $deleteurl = new moodle_url('/admin/tool/mucertify/management/period_delete.php', ['id' => $period->id]);
                $deletebutton = new \tool_mulib\output\dialog_form\button($deleteurl, get_string('period_delete', 'tool_mucertify'));
                $deletebutton->set_after_submit($deletebutton::AFTER_SUBMIT_REDIRECT);
                $buttons[] = $this->output->render($deletebutton);
            }
        }

        $result = '';

        $result .= '<dl class="row">';

        $programname = $strnotset;
        if ($program) {
            $programname = format_string($program->fullname);
            if ($programcontext && has_capability('tool/muprog:view', $programcontext)) {
                if ($allocation) {
                    $url = new moodle_url('/admin/tool/muprog/management/user_allocation.php', ['id' => $allocation->id]);
                } else {
                    $url = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
                }
                $programname = html_writer::link($url, $programname);
            }
        }
        $result .= '<dt class="col-3">' . get_string('program', 'tool_muprog') . '</dt><dd class="col-9">'
            . $programname . '</dd>';

        if ($allocation) {
            $programstatus = \tool_muprog\local\allocation::get_completion_status_html($program, $allocation);
        } else {
            $programstatus = get_string('notallocated', 'tool_mucertify');
        }
        $result .= '<dt class="col-3">' . get_string('programstatus', 'tool_muprog') . '</dt><dd class="col-9">'
            . $programstatus . '</dd>';

        $result .= '<dt class="col-3">' . get_string('windowstartdate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_windowstart_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('windowduedate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_windowdue_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('windowenddate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_windowend_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('fromdate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_from_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('untildate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_until_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('recertify', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_recertify_html($certification, $assignment, $period) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('certifieddate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . ($period->timecertified ? userdate($period->timecertified) : $strnotset) . '</dd>';

        $result .= '<dt class="col-3">' . get_string('revokeddate', 'tool_mucertify') . '</dt><dd class="col-9">'
            . ($period->timerevoked ? userdate($period->timerevoked) : $strnotset) . '</dd>';

        if (!empty($certification->templateid) && \tool_mucertify\local\certificate::is_available()) {
            $template = $DB->get_record('tool_certificate_templates', ['id' => $certification->templateid]);
            if ($template) {
                $issuecode = '&nbsp;';
                if ($period->certificateissueid) {
                    $templatecontext = \context::instance_by_id($template->contextid);
                    $issue = $DB->get_record('tool_certificate_issues', ['id' => $period->certificateissueid]);
                    if ($issue) {
                        $issuecode = $issue->code;
                        if (has_capability('tool/certificate:viewallcertificates', $templatecontext)) {
                            $url = \tool_certificate\template::view_url($issue->code);
                            $issuecode = \html_writer::link($url, $issuecode, ['target' => '_blank']);
                        }
                    } else {
                        $issuecode = get_string('error');
                    }
                }
                $result .= '<dt class="col-3">' . get_string('certificate', 'tool_certificate') . '</dt><dd class="col-9">'
                    . $issuecode . '</dd>';
            }
        }

        if ($period->evidencejson) {
            $jsondata = (object)json_decode($period->evidencejson);
            if (isset($jsondata->details)) {
                $result .= '<dt class="col-3">' . get_string('evidence_details', 'tool_mucertify') . '</dt><dd class="col-9">'
                    . format_text($jsondata->details, FORMAT_PLAIN, ['para' => false]) . '</dd>';
            }
        }

        $result .= '<dt class="col-3">' . get_string('periodstatus', 'tool_mucertify') . '</dt><dd class="col-9">'
            . period::get_status_html($certification, $assignment, $period) . '</dd>';

        $result .= '</dl>';

        if ($buttons) {
            $result .= '<div class="buttons mb-5">';
            $result .= implode(' ', $buttons);
            $result .= '</div>';
        }

        return $result;
    }

    /**
     * Render notifications.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @return string
     */
    public function render_user_notifications(stdClass $certification, stdClass $assignment): string {
        $strnotset = get_string('notset', 'tool_mucertify');

        $result = $this->output->heading(get_string('notificationdates', 'tool_mucertify'), 3, ['h4']);

        $result .= '<dl class="row">';

        $types = notification_manager::get_all_types();
        // phpcs:ignore moodle.Commenting.InlineComment.TypeHintingForeach
        /** @var class-string<\tool_mucertify\local\notification\base> $classname */
        foreach ($types as $notificationtype => $classname) {
            if ($notificationtype === 'unassignment') {
                continue;
            }
            $result .= '<dt class="col-3">';
            $result .= $classname::get_name();
            $result .= '</dt><dd class="col-9">';
            $timenotified = notification_manager::get_timenotified($assignment->userid, $certification->id, $notificationtype);
            $result .= ($timenotified ? userdate($timenotified) : $strnotset);
            $result .= '</dd>';
        }

        return $result;
    }

    /**
     * Render sources.
     *
     * @param stdClass $certification
     * @return string
     */
    public function render_certification_sources(stdClass $certification): string {
        global $DB;

        $result = '';

        $sources = [];
        /** @var \tool_mucertify\local\source\base[] $sourceclasses */
        $sourceclasses = assignment::get_source_classes();
        foreach ($sourceclasses as $sourcetype => $sourceclass) {
            $sourcerecord = $DB->get_record('tool_mucertify_source', ['type' => $sourcetype, 'certificationid' => $certification->id]);
            if (!$sourcerecord && !$sourceclass::is_new_allowed($certification)) {
                continue;
            }
            if (!$sourcerecord) {
                $sourcerecord = null;
            }
            $sources[$sourcetype] = $sourceclass::render_status($certification, $sourcerecord);
        }

        if ($sources) {
            $result .= '<dl class="row">';
            foreach ($sources as $sourcetype => $status) {
                $name = $sourceclasses[$sourcetype]::get_name();
                $result .= '<dt class="col-3">' . $name . '</dt><dd class="col-9">' . $status . '</dd>';
            }
            $result .= '</dl>';
        } else {
            $result = get_string('notavailable');
        }

        return $result;
    }
}
