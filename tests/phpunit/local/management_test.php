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

namespace tool_mutrain\phpunit\local;

use tool_mutrain\local\management;

/**
 * Training management helper test.
 *
 * @group      muTMS
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\local\management
 */
final class management_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_management_url(): void {
        global $DB;

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $admin = get_admin();
        $guest = guest_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        \role_assign($managerrole->id, $manager->id, $catcontext2->id);

        $viewer = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(management::get_management_url());

        $this->setUser($admin);
        $expected = new \moodle_url('/admin/tool/mutrain/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($manager);
        $this->assertNull(management::get_management_url());

        $this->setUser($viewer);
        $this->assertNull(management::get_management_url());
    }

    public function test_get_management_url_tenant(): void {
        if (!\tool_mutrain\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $tenantgenerator->create_tenant();
        $tenantcatcontext = \context_coursecat::instance($tenant->categoryid);
        $syscontext = \context_system::instance();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $viewerroleid, $syscontext);

        $viewer0 = $this->getDataGenerator()->create_user();
        role_assign($viewerroleid, $viewer0->id, $syscontext->id);

        $viewer1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        role_assign($viewerroleid, $viewer1->id, $tenantcatcontext->id);

        $this->setUser($viewer0);
        $expected = new \moodle_url('/admin/tool/mutrain/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($viewer1);
        $expected = new \moodle_url('/admin/tool/mutrain/management/index.php', ['contextid' => $tenantcatcontext->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());
    }

    public function test_fetch_frameworks(): void {
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework(['name' => 'hokus']);
        $framework2 = $generator->create_framework(['idnumber' => 'pokus']);
        $framework3 = $generator->create_framework(['archived' => 1]);
        $framework4 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework5 = $generator->create_framework(['contextid' => $catcontext1->id, 'archived' => 1]);
        $framework6 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $result = management::fetch_frameworks(null, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(4, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);
        $this->assertArrayHasKey($framework2->id, $frameworks);
        $this->assertArrayHasKey($framework4->id, $frameworks);
        $this->assertArrayHasKey($framework6->id, $frameworks);

        $result = management::fetch_frameworks(null, false, 'hokus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(1, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);

        $result = management::fetch_frameworks(null, false, 'okus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(2, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework1->id, $frameworks);
        $this->assertArrayHasKey($framework2->id, $frameworks);

        $result = management::fetch_frameworks(null, true, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(2, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework3->id, $frameworks);
        $this->assertArrayHasKey($framework5->id, $frameworks);

        $result = management::fetch_frameworks($catcontext1, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(1, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework4->id, $frameworks);

        $result = management::fetch_frameworks(null, false, '', 1, 2, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework4->id, $frameworks);
        $this->assertArrayHasKey($framework6->id, $frameworks);

        $result = management::fetch_frameworks(null, false, '', 3, 1, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['frameworks']);
        $this->assertSame(4, $result['totalcount']);
        $frameworks = $result['frameworks'];
        $this->assertArrayHasKey($framework6->id, $frameworks);
    }

    public function test_get_used_contexts_menu(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $category3 = $this->getDataGenerator()->create_category([]);
        $catcontext3 = \context_coursecat::instance($category3->id);

        $user = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', \MUST_EXIST);
        \role_assign($managerrole->id, $user->id, $catcontext1);
        \role_assign($managerrole->id, $user->id, $catcontext3);
        // Undo work hackery.
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', \MUST_EXIST);
        \assign_capability('moodle/category:viewcourselist', CAP_ALLOW, $managerrole->id, $syscontext->id);
        $coursecatcache = \cache::make('core', 'coursecat');
        $coursecatcache->purge();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework();
        $framework2 = $generator->create_framework();
        $framework3 = $generator->create_framework();
        $framework4 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework5 = $generator->create_framework(['contextid' => $catcontext1->id]);
        $framework6 = $generator->create_framework(['contextid' => $catcontext2->id]);

        $this->setAdminUser();
        $expected = [
            0 => 'All frameworks (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
        ];
        $contexts = management::get_used_contexts_menu($syscontext);
        $this->assertSame($expected, $contexts);

        $expected = [
            0 => 'All frameworks (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);

        $this->setUser($user);
        $coursecatcache->purge();

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
        ];
        $contexts = management::get_used_contexts_menu($catcontext1);
        $this->assertSame($expected, $contexts);

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);
    }

    public function test_get_framework_search_query(): void {
        global $DB;

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $framework1 = $generator->create_framework(['name' => 'First framework', 'idnumber' => 'PRG1', 'description' => 'prvni popis']);
        $framework2 = $generator->create_framework(['name' => 'Second framework', 'idnumber' => 'PRG2', 'description' => 'druhy popis']);
        $framework3 = $generator->create_framework(['name' => 'Third framework', 'idnumber' => 'PR3', 'description' => 'treti popis', 'contextid' => $catcontext1->id]);

        list($search, $params) = management::get_framework_search_query(null, 'First', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'First', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {tool_mutrain_framework} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework1->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, 'popis', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query(null, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework1->id, $framework2->id, $framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, '', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PR', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PR', '');
        $frameworkids = $DB->get_fieldset_sql("SELECT * FROM {tool_mutrain_framework} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$framework3->id], $frameworkids);

        list($search, $params) = management::get_framework_search_query($catcontext1, 'PRG', 'p');
        $frameworkids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_mutrain_framework} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([], $frameworkids);
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/mutrain/management/index.php'),
            $syscontext
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/mutrain/management/index.php'),
            $syscontext
        );
    }

    public function test_setup_framework_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework1 = $generator->create_framework();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \moodle_url('/admin/tool/mutrain/management/new.php'),
            $syscontext,
            $framework1
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_framework_page(
            new \moodle_url('/admin/tool/mutrain/management/new.php'),
            $syscontext,
            $framework1
        );
    }
}
