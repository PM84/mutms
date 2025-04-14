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

namespace tool_mucertify\table;

use stdClass;
use moodle_url;
use tool_mucertify\local\period;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Periods for given assignment.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_periods extends \table_sql {
    /** @var int per page */
    const DEFAULT_PERPAGE = 99;

    /** @var stdClass */
    public $assignment;
    /** @var stdClass */
    public $certification;
    /** @var \context */
    public $context;
    /** @var string */
    public $search = '';

    /**
     * Constructor.
     *
     * @param stdClass $certification
     * @param stdClass $assignment
     * @param moodle_url $url
     */
    public function __construct(stdClass $certification, stdClass $assignment, moodle_url $url) {
        parent::__construct('tool_mucertify_assignment_periods');

        $this->assignment = $assignment;
        $this->certification = $certification;
        $this->context = \context::instance_by_id($certification->contextid);

        $page = optional_param('page', 0, PARAM_INT);

        $params = [];
        if ($page > 0) {
            $params['page'] = $page;
            $this->currpage = $page;
        }
        $baseurl = new moodle_url($url, $params);
        $this->define_baseurl($baseurl);
        $this->pagesize = self::DEFAULT_PERPAGE;

        $this->collapsible(false);
        $this->sortable(false, 'timewindowstart', SORT_DESC);
        $this->pageable(false);
        $this->is_downloadable(false);

        $columns = [
            'timewindowstart',
            'timewindowdue',
            'timewindowend',
            'program',
            'timeuntil',
        ];
        if ($this->certification->recertify) {
            $columns[] = 'recertify';
        }
        $columns[] = 'status';
        $headers = [
            get_string('windowstartdate', 'tool_mucertify'),
            get_string('windowduedate', 'tool_mucertify'),
            get_string('windowenddate', 'tool_mucertify'),
            get_string('program', 'tool_muprog'),
            get_string('untildate', 'tool_mucertify'),
        ];
        if ($this->certification->recertify) {
            $headers[] = get_string('recertify', 'tool_mucertify');
        }
        $headers[] = get_string('periodstatus', 'tool_mucertify');

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->set_attribute('id', 'tool_mucertify_assignment_periods_table');

        $params = ['assignmentid' => $this->assignment->id];

        $sql = "SELECT p.*, pr.fullname AS programfullname, pr.contextid as programcontextid
                  FROM {tool_mucertify_period} p
                  JOIN {tool_mucertify_assignment} a ON a.userid = p.userid AND a.certificationid = p.certificationid
             LEFT JOIN {tool_muprog_program} pr ON pr.id = p.programid
                 WHERE a.id = :assignmentid";

        $this->set_sql("*", "($sql) xperiod", '1=1', $params);
    }

    /**
     * Display program name.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_program(stdClass $period): string {
        global $DB, $USER;
        if ($period->programfullname !== null) {
            $name = format_string($period->programfullname);
            $context = \context::instance_by_id($period->programcontextid, IGNORE_MISSING);
            if ($context && has_capability('tool/muprog:view', $context)) {
                if ($period->allocationid && $DB->record_exists('tool_muprog_allocation', ['id' => $period->allocationid])) {
                    $url = new moodle_url('/admin/tool/muprog/management/user_allocation.php', ['id' => $period->allocationid]);
                } else {
                    $url = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $period->programid]);
                }
                $name = \html_writer::link($url, $name);
            }
            return $name;
        }
        return get_string('notset', 'tool_mucertify');
    }

    /**
     * Display the window start date linked to period details page.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_timewindowstart(stdClass $period): string {
        $start = period::get_windowstart_html($this->certification, $this->assignment, $period, true);
        $url = new moodle_url('/admin/tool/mucertify/management/period.php', ['id' => $period->id]);
        return \html_writer::link($url, $start);
    }

    /**
     * Display the certification due date.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_timewindowdue(stdClass $period): string {
        return period::get_windowdue_html($this->certification, $this->assignment, $period, true);
    }

    /**
     * Display the window closing date.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_timewindowend(stdClass $period): string {
        return period::get_windowend_html($this->certification, $this->assignment, $period, true);
    }

    /**
     * Display the until date.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_timeuntil(stdClass $period): string {
        return period::get_until_html($this->certification, $this->assignment, $period, true);
    }

    /**
     * Display status.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_status(stdClass $period): string {
        return period::get_status_html($this->certification, $this->assignment, $period);
    }

    /**
     * Display recertify date.
     *
     * @param stdClass $period
     * @return string html used to display the plan name
     */
    public function col_recertify(stdClass $period): string {
        return period::get_recertify_html($this->certification, $this->assignment, $period, true);
    }

    /**
     * No results.
     */
    public function print_nothing_to_display(): void {
        // Get rid of ugly H2 heading.
        echo '<em>' . get_string('nothingtodisplay') . '</em>';
    }
}
