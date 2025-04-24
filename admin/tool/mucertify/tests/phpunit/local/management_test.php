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

namespace tool_mucertify\phpunit\local;

use tool_mucertify\local\management;

/**
 * certification management helper test.
 *
 * @group      muTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\management
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
        \assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);
        \role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(management::get_management_url());

        $this->setUser($admin);
        $expected = new \moodle_url('/admin/tool/mucertify/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($manager);
        $this->assertNull(management::get_management_url());

        $this->setUser($viewer);
        $this->assertNull(management::get_management_url());
    }

    public function test_get_management_url_tenant(): void {
        if (!\tool_mucertify\local\util::is_mutenancy_available()) {
            $this->markTestSkipped('multitenancy not available');
        }
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant = $tenantgenerator->create_tenant();
        $tenantcatcontext = \context_coursecat::instance($tenant->categoryid);
        $syscontext = \context_system::instance();

        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mucertify:view', CAP_ALLOW, $viewerroleid, $syscontext);

        $viewer0 = $this->getDataGenerator()->create_user();
        role_assign($viewerroleid, $viewer0->id, $syscontext->id);

        $viewer1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        role_assign($viewerroleid, $viewer1->id, $tenantcatcontext->id);

        $this->setUser($viewer0);
        $expected = new \moodle_url('/admin/tool/mucertify/management/index.php');
        $this->assertSame((string)$expected, (string)management::get_management_url());

        $this->setUser($viewer1);
        $expected = new \moodle_url('/admin/tool/mucertify/management/index.php', ['contextid' => $tenantcatcontext->id]);
        $this->assertSame((string)$expected, (string)management::get_management_url());
    }

    public function test_fetch_certifications(): void {
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification(['fullname' => 'hokus']);
        $certification2 = $generator->create_certification(['idnumber' => 'pokus']);
        $certification3 = $generator->create_certification();
        $certification4 = $generator->create_certification(['contextid' => $catcontext1->id]);
        $certification5 = $generator->create_certification(['contextid' => $catcontext1->id]);
        $certification6 = $generator->create_certification(['contextid' => $catcontext2->id]);

        $certification3 = \tool_mucertify\local\certification::archive($certification3->id);
        $certification5 = \tool_mucertify\local\certification::archive($certification5->id);

        $result = management::fetch_certifications(null, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(4, $result['certifications']);
        $this->assertSame(4, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification1->id, $certifications);
        $this->assertArrayHasKey($certification2->id, $certifications);
        $this->assertArrayHasKey($certification4->id, $certifications);
        $this->assertArrayHasKey($certification6->id, $certifications);

        $result = management::fetch_certifications(null, false, 'hokus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['certifications']);
        $this->assertSame(1, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification1->id, $certifications);

        $result = management::fetch_certifications(null, false, 'okus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['certifications']);
        $this->assertSame(2, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification1->id, $certifications);
        $this->assertArrayHasKey($certification2->id, $certifications);

        $result = management::fetch_certifications(null, true, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['certifications']);
        $this->assertSame(2, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification3->id, $certifications);
        $this->assertArrayHasKey($certification5->id, $certifications);

        $result = management::fetch_certifications($catcontext1, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['certifications']);
        $this->assertSame(1, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification4->id, $certifications);

        $result = management::fetch_certifications(null, false, '', 1, 2, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['certifications']);
        $this->assertSame(4, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification4->id, $certifications);
        $this->assertArrayHasKey($certification6->id, $certifications);

        $result = management::fetch_certifications(null, false, '', 3, 1, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['certifications']);
        $this->assertSame(4, $result['totalcount']);
        $certifications = $result['certifications'];
        $this->assertArrayHasKey($certification6->id, $certifications);
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
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        \role_assign($managerrole->id, $user->id, $catcontext1);
        \role_assign($managerrole->id, $user->id, $catcontext3);
        // Undo work hackery.
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        \assign_capability('moodle/category:viewcourselist', CAP_ALLOW, $managerrole->id, $syscontext->id);
        $coursecatcache = \cache::make('core', 'coursecat');
        $coursecatcache->purge();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification();
        $certification2 = $generator->create_certification();
        $certification3 = $generator->create_certification();
        $certification4 = $generator->create_certification(['contextid' => $catcontext1->id]);
        $certification5 = $generator->create_certification(['contextid' => $catcontext1->id]);
        $certification6 = $generator->create_certification(['contextid' => $catcontext2->id]);

        $this->setAdminUser();
        $expected = [
            0 => 'All certifications (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
        ];
        $contexts = management::get_used_contexts_menu($syscontext);
        $this->assertSame($expected, $contexts);

        $expected = [
            0 => 'All certifications (6)',
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

    public function test_fetch_current_cohorts_menu(): void {
        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort A']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort B']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort C']);

        $certification1 = $generator->create_certification();
        $certification2 = $generator->create_certification();
        $certification3 = $generator->create_certification();

        \tool_mucertify\local\certification::update_visibility((object)[
            'id' => $certification1->id,
            'public' => 0,
            'cohortids' => [$cohort1->id, $cohort2->id],
        ]);
        \tool_mucertify\local\certification::update_visibility((object)[
            'id' => $certification2->id,
            'public' => 1,
            'cohortids' => [$cohort3->id],
        ]);

        $expected = [
            $cohort1->id => $cohort1->name,
            $cohort2->id => $cohort2->name,
        ];
        $menu = management::fetch_current_cohorts_menu($certification1->id);
        $this->assertSame($expected, $menu);

        $menu = management::fetch_current_cohorts_menu($certification3->id);
        $this->assertSame([], $menu);
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/mucertify/management/index.php'),
            $syscontext
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_index_page(
            new \moodle_url('/admin/tool/mucertify/management/index.php'),
            $syscontext
        );
    }

    public function test_setup_certification_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $certification1 = $generator->create_certification();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        management::setup_certification_page(
            new \moodle_url('/admin/tool/mucertify/management/new.php'),
            $syscontext,
            $certification1,
            'certification_general'
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        management::setup_certification_page(
            new \moodle_url('/admin/tool/mucertify/management/new.php'),
            $syscontext,
            $certification1,
            'certification_general'
        );
    }
}
