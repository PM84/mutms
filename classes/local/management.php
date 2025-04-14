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

namespace tool_mucertify\local;

use moodle_url, stdClass;

/**
 * Certification management helper.
 *
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access certification management UI.
     *
     * @return moodle_url|null
     */
    public static function get_management_url(): ?moodle_url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }
        if (has_capability('tool/mucertify:view', \context_system::instance())) {
            return new moodle_url('/admin/tool/mucertify/management/index.php');
        } else {
            // This is not very fast, but we need to let users somehow access certification
            // management if they can do so in course category only.
            $categories = \core_course_category::make_categories_list('tool/mucertify:view');
            // NOTE: Add some better logic here looking for categories with certifications or remember which one was accessed before.
            if ($categories) {
                foreach ($categories as $cid => $unusedname) {
                    $catcontext = \context_coursecat::instance($cid, IGNORE_MISSING);
                    if ($catcontext) {
                        return new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Fetch list of certifications.
     *
     * @param \context|null $context null means all contexts
     * @param bool $archived
     * @param string $search search string
     * @param int $page
     * @param int $perpage
     * @param string $orderby
     * @return array ['certifications' => array, 'totalcount' => int]
     */
    public static function fetch_certifications(?\context $context, bool $archived, string $search, int $page, int $perpage, string $orderby = 'fullname ASC'): array {
        global $DB;

        list($select, $params) = self::get_search_query($context, $search, '');

        $select .= ' AND archived = :archived';
        $params['archived'] = (int)$archived;

        $certifications = $DB->get_records_select('tool_mucertify_certification', $select, $params, $orderby, '*', $page * $perpage, $perpage);
        $totalcount = $DB->count_records_select('tool_mucertify_certification', $select, $params);

        return ['certifications' => $certifications, 'totalcount' => $totalcount];
    }

    /**
     * Fetch list contexts with certifications that users may access.
     *
     * @param \context $context current management context, added if no certification present yet
     * @return array
     */
    public static function get_used_contexts_menu(\context $context): array {
        global $DB;

        $syscontext = \context_system::instance();

        $result = [];

        if (has_capability('tool/mucertify:view', $syscontext)) {
            $allcount = $DB->count_records('tool_mucertify_certification', []);
            $result[0] = get_string('allcertifications', 'tool_mucertify') . ' (' . $allcount . ')';

            $syscount = $DB->count_records('tool_mucertify_certification', ['contextid' => $syscontext->id]);
            $result[$syscontext->id] = $syscontext->get_context_name() . ' (' . $syscount .')';
        }

        $categories = \core_course_category::make_categories_list('tool/mucertify:view');
        if (!$categories) {
            return $result;
        }

        $sql = "SELECT cat.id, COUNT(p.id)
                  FROM {course_categories} cat
                  JOIN {context} ctx ON ctx.instanceid = cat.id AND ctx.contextlevel = 40
                  JOIN {tool_mucertify_certification} p ON p.contextid = ctx.id
              GROUP BY cat.id
                HAVING COUNT(p.id) > 0";
        $certificationcounts = $DB->get_records_sql_menu($sql);

        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid, IGNORE_MISSING);
            if (!$catcontext) {
                continue;
            }
            if (!isset($certificationcounts[$catid])) {
                if ($catcontext->id == $context->id) {
                    $result[$catcontext->id] = $categoryname;
                }
                continue;
            }
            $result[$catcontext->id] = $categoryname . ' (' . $certificationcounts[$catid] . ')';
        }

        if (!isset($result[$context->id])) {
            $result[$context->id] = $context->get_context_name();
        }

        return $result;
    }

    /**
     * Returns search query for certifications without any access control logic.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return array
     */
    public static function get_search_query(?\context $context, string $search, string $tablealias = ''): array {
        global $DB;

        if ($tablealias !== '' && substr($tablealias, -1) !== '.') {
            $tablealias .= '.';
        }

        $conditions = [];
        $params = [];

        if ($context) {
            $conditions[] = $tablealias . 'contextid = :prgcontextid';
            $params['prgcontextid'] = $context->id;
        }

        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $conditions = [];
            $fields = ['fullname', 'idnumber', 'description'];
            $cnt = 0;
            foreach ($fields as $field) {
                $conditions[] = $DB->sql_like($tablealias . $field, ':prgsearch' . $cnt, false);
                $params['prgsearch' . $cnt] = $searchparam;
                $cnt++;
            }
        }

        if ($conditions) {
            $sql = '(' . implode(' OR ', $conditions) . ')';
            return [$sql, $params];
        } else {
            return ['1=1', $params];
        }
    }

    /**
     * Fetch cohorts that allow certification visibility.
     *
     * @param int $certificationid
     * @return array
     */
    public static function fetch_current_cohorts_menu(int $certificationid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_mucertify_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.certificationid = :certificationid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['certificationid' => $certificationid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Set up $PAGE for certification management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @return void
     */
    public static function setup_index_page(\moodle_url $pageurl, \context $context): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('certifications', 'tool_mucertify'));
        $PAGE->set_heading(get_string('certifications', 'tool_mucertify'));
        $PAGE->set_secondary_navigation(false);

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/mucertify:view', $context)) {
                $url = new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
    }

    /**
     * Set up $PAGE for certification management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @param stdClass $certification
     * @param string $secondarytab
     * @return void
     */
    public static function setup_certification_page(\moodle_url $pageurl, \context $context, stdClass $certification, string $secondarytab): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('certifications', 'tool_mucertify'));
        $PAGE->set_heading(format_string($certification->fullname));

        $secondarynav = new \tool_mucertify\navigation\views\certification_secondary($PAGE, $certification);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/mucertify:view', $context)) {
                $url = new moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
        $PAGE->navbar->add(format_string($certification->fullname));
    }
}
