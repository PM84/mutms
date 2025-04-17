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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutrain\local;

use moodle_url, stdClass;

/**
 * Training management helper.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access framework management UI.
     *
     * @return moodle_url|null
     */
    public static function get_management_url(): ?moodle_url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }
        if (has_capability('tool/mutrain:viewframeworks', \context_system::instance())) {
            return new moodle_url('/admin/tool/mutrain/management/index.php');
        } else {
            // This is not very fast, but we need to let users somehow access framework
            // management if they can do so in course category only.
            $categories = \core_course_category::make_categories_list('tool/mutrain:viewframeworks');
            // NOTE: Add some better logic here looking for categories with frameworks or remember which one was accessed before.
            if ($categories) {
                foreach ($categories as $cid => $unusedname) {
                    $catcontext = \context_coursecat::instance($cid, IGNORE_MISSING);
                    if ($catcontext) {
                        return new moodle_url('/admin/tool/mutrain/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Fetch list of frameworks.
     *
     * @param \context|null $context null means all contexts
     * @param bool $archived
     * @param string $search search string
     * @param int $page
     * @param int $perpage
     * @param string $orderby
     * @return array ['frameworks' => array, 'totalcount' => int]
     */
    public static function fetch_frameworks(?\context $context, bool $archived, string $search, int $page, int $perpage, string $orderby = 'name ASC'): array {
        global $DB;

        list($select, $params) = self::get_framework_search_query($context, $search, '');

        $select .= ' AND archived = :archived';
        $params['archived'] = (int)$archived;

        $frameworks = $DB->get_records_select('tool_mutrain_framework', $select, $params, $orderby, '*', $page * $perpage, $perpage);
        $totalcount = $DB->count_records_select('tool_mutrain_framework', $select, $params);

        return ['frameworks' => $frameworks, 'totalcount' => $totalcount];
    }

    /**
     * Fetch list contexts with frameworks that users may access.
     *
     * @param \context $context current management context, added if no framework present yet
     * @return array
     */
    public static function get_used_contexts_menu(\context $context): array {
        global $DB;

        $syscontext = \context_system::instance();

        $result = [];

        if (has_capability('tool/mutrain:viewframeworks', $syscontext)) {
            $allcount = $DB->count_records('tool_mutrain_framework', []);
            $result[0] = get_string('allframeworks', 'tool_mutrain') . ' (' . $allcount . ')';

            $syscount = $DB->count_records('tool_mutrain_framework', ['contextid' => $syscontext->id]);
            $result[$syscontext->id] = $syscontext->get_context_name() . ' (' . $syscount .')';
        }

        $categories = \core_course_category::make_categories_list('tool/mutrain:viewframeworks');
        if (!$categories) {
            return $result;
        }

        $sql = "SELECT cat.id, COUNT(f.id)
                  FROM {course_categories} cat
                  JOIN {context} ctx ON ctx.instanceid = cat.id AND ctx.contextlevel = 40
                  JOIN {tool_mutrain_framework} f ON f.contextid = ctx.id
              GROUP BY cat.id
                HAVING COUNT(f.id) > 0";
        $frameworkcounts = $DB->get_records_sql_menu($sql);

        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid, IGNORE_MISSING);
            if (!$catcontext) {
                continue;
            }
            if (!isset($frameworkcounts[$catid])) {
                if ($catcontext->id == $context->id) {
                    $result[$catcontext->id] = $categoryname;
                }
                continue;
            }
            $result[$catcontext->id] = $categoryname . ' (' . $frameworkcounts[$catid] . ')';
        }

        if (!isset($result[$context->id])) {
            $result[$context->id] = $context->get_context_name();
        }

        return $result;
    }

    /**
     * Returns framework query data.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return array
     */
    public static function get_framework_search_query(?\context $context, string $search, string $tablealias = ''): array {
        global $DB;

        if ($tablealias !== '' && substr($tablealias, -1) !== '.') {
            $tablealias .= '.';
        }

        $conditions = [];
        $params = [];

        if ($context) {
            $contextselect = 'AND ' . $tablealias . 'contextid = :frwcontextid';
            $params['frwcontextid'] = $context->id;
        } else {
            $contextselect = '';
        }

        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $fields = ['name', 'idnumber', 'description'];
            $cnt = 0;
            foreach ($fields as $field) {
                $conditions[] = $DB->sql_like($tablealias . $field, ':frwsearch' . $cnt, false);
                $params['frwsearch' . $cnt] = $searchparam;
                $cnt++;
            }
        }

        if ($conditions) {
            $sql = '(' . implode(' OR ', $conditions) . ') ' . $contextselect;
            return [$sql, $params];
        } else {
            return ['1=1 ' . $contextselect, $params];
        }
    }

    /**
     * Set up $PAGE for framework management UI.
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
        $PAGE->set_title(get_string('frameworks', 'tool_mutrain'));
        $PAGE->set_heading(get_string('frameworks', 'tool_mutrain'));
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
            if (has_capability('tool/mutrain:viewframeworks', $context)) {
                $url = new moodle_url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
    }

    /**
     * Set up $PAGE for framework management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @param stdClass $framework
     * @return void
     */
    public static function setup_framework_page(\moodle_url $pageurl, \context $context, stdClass $framework): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('frameworks', 'tool_mutrain'));
        $PAGE->set_heading(format_string($framework->name));
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
            if (has_capability('tool/mutrain:viewframeworks', $context)) {
                $url = new moodle_url('/admin/tool/mutrain/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
        $PAGE->navbar->add(format_string($framework->name));
    }
}
