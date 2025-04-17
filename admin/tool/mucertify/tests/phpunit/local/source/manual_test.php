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

namespace tool_mucertify\phpunit\local\source;

use tool_mucertify\local\certification;

/**
 * Certification manual assignment source test.
 *
 * @group      muTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\source\manual
 */
final class manual_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('manual', \tool_mucertify\local\source\manual::get_type());
    }

    public function test_get_name(): void {
        $this->assertSame('Manual assignment', \tool_mucertify\local\source\manual::get_name());
    }

    public function test_is_new_allowed(): void {
        $certification = new \stdClass();
        $this->assertSame(true, \tool_mucertify\local\source\manual::is_new_allowed($certification));
    }

    public function test_is_update_allowed(): void {
        $certification = new \stdClass();
        $this->assertSame(true, \tool_mucertify\local\source\manual::is_update_allowed($certification));
    }

    public function test_fix_assignments(): void {
        $result = \tool_mucertify\local\source\manual::fix_assignments(null, null);
        $this->assertFalse($result);
    }

    public function test_assignment_edit_supported(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $assignment = new \stdClass();
        $result = \tool_mucertify\local\source\manual::assignment_edit_supported($certification, $source, $assignment);
        $this->assertTrue($result);
    }

    public function test_assignment_delete_supported(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $assignment = new \stdClass();

        $assignment->archived = '0';
        $result = \tool_mucertify\local\source\manual::assignment_delete_supported($certification, $source, $assignment);
        $this->assertTrue($result);

        $assignment->archived = '1';
        $result = \tool_mucertify\local\source\manual::assignment_delete_supported($certification, $source, $assignment);
        $this->assertTrue($result);
    }

    public function test_is_assignment_possible(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $result = \tool_mucertify\local\source\manual::is_assignment_possible($certification, $source);
        $this->assertTrue($result);

        $certification->archived = '1';
        $result = \tool_mucertify\local\source\manual::is_assignment_possible($certification, $source);
        $this->assertFalse($result);
        $certification->archived = '0';

        $certification->programid1 = null;
        $result = \tool_mucertify\local\source\manual::is_assignment_possible($certification, $source);
        $this->assertFalse($result);
    }

    public function test_get_catalogue_actions(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $this->assertSame([], \tool_mucertify\local\source\manual::get_catalogue_actions($certification, $source));
    }

    public function test_decode_datajson(): void {
        $source = new \stdClass();
        $this->assertSame($source, \tool_mucertify\local\source\manual::decode_datajson($source));
    }

    public function test_encode_datajson(): void {
        $formdata = new \stdClass();
        $this->assertSame('[]', \tool_mucertify\local\source\manual::encode_datajson($formdata));
    }

    public function test_add_management_certification_users_actions(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:assign', CAP_ALLOW, $editorroleid, $syscontext);
        \role_assign($editorroleid, $user1->id, $catcontext->id);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'contextid' => $catcontext->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setUser($user2);
        $actions = new \tool_mulib\output\header_actions('xyz');
        \tool_mucertify\local\source\manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertFalse($actions->has_items());

        $this->setUser($user1);
        $actions = new \tool_mulib\output\header_actions('xyz');
        \tool_mucertify\local\source\manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertTrue($actions->has_items());

        $certification->archived = '1';
        $actions = new \tool_mulib\output\header_actions('xyz');
        \tool_mucertify\local\source\manual::add_management_certification_users_actions($actions, $certification, $source);
        $this->assertFalse($actions->has_items());
        $certification->archived = '0';
    }

    public function test_update_source(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $data = [
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'manual',
            'enable' => 0,
        ];
        $source = \tool_mucertify\local\source\manual::update_source((object)$data);
        $this->assertSame(null, $source);

        $data = [
            'certificationid' => $certification->id,
            'type' => 'manual',
            'enable' => 1,
        ];
        $source = \tool_mucertify\local\source\manual::update_source((object)$data);
        $this->assertSame($certification->id, $source->certificationid);
        $this->assertSame('manual', $source->type);
    }

    public function test_assign_users(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setCurrentTimeStart();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($program1->id, $period->programid);
        $this->assertTimeCurrent($period->timewindowstart);
        $this->assertSame((string)($period->timewindowstart + DAYSECS), $period->timewindowdue);
        $this->assertSame((string)($period->timewindowstart + 2 * DAYSECS), $period->timewindowend);
        $this->assertSame(null, $period->allocationid);
        $this->assertSame(null, $period->timecertified);
        $this->assertSame(null, $period->timefrom);
        $this->assertSame(null, $period->timeuntil);
        $this->assertSame(null, $period->timerevoked);
        $this->assertSame('1', $period->first);
        $this->assertSame('1', $period->recertifiable);

        $now = time();
        $this->setCurrentTimeStart();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user2->id], [
            'timewindowstart' => $now - DAYSECS,
            'timewindowdue' => null,
            'timewindowend' => $now + DAYSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($program1->id, $period->programid);
        $this->assertSame((string)($now - DAYSECS), $period->timewindowstart);
        $this->assertSame(null, $period->timewindowdue);
        $this->assertSame((string)($now + DAYSECS), $period->timewindowend);
        $this->assertSame(null, $period->allocationid);
        $this->assertSame(null, $period->timecertified);
        $this->assertSame(null, $period->timefrom);
        $this->assertSame(null, $period->timeuntil);
        $this->assertSame(null, $period->timerevoked);
        $this->assertSame('1', $period->first);
        $this->assertSame('1', $period->recertifiable);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => null,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]);
        $this->assertFalse($period);

        $now = time();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user2->id], [
            'timecertifiedtemp' => $now + WEEKSECS,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame((string)($now + WEEKSECS), $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id]);
        $this->assertFalse($period);

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setCurrentTimeStart();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user3->id], [
            'noperiod' => 1,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user3->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user3->id]));

        $this->setCurrentTimeStart();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user4->id], [
            'noperiod' => 0,
        ]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user4->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($source->id, $assignment->sourceid);
        $this->assertSame('[]', $assignment->sourcedatajson);
        $this->assertSame('0', $assignment->archived);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame('[]', $assignment->evidencejson);
        $this->assertTimeCurrent($assignment->timecreated);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user4->id]));
    }

    public function test_unassign_user(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
            'periods_due1' => DAYSECS,
            'periods_windowend1' => ['since' => certification::SINCE_WINDOWSTART, 'delay' => 'P2D'],
            'periods_expiration1' => ['since' => certification::SINCE_NEVER, 'delay' => null],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->setCurrentTimeStart();
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id, $user2->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]));

        \tool_mucertify\local\source\manual::unassign_user($certification, $source, $assignment);
        $this->assertCount(0, $DB->get_records('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(0, $DB->get_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id]));
        $this->assertCount(1, $DB->get_records('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id]));
    }

    public function test_render_status_details(): void {
        $certification = new \stdClass();
        $source = new \stdClass();
        $this->assertSame('Active', \tool_mucertify\local\source\manual::render_status_details($certification, $source));
        $this->assertSame('Inactive', \tool_mucertify\local\source\manual::render_status_details($certification, null));
    }

    public function test_render_status(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        \assign_capability('tool/mucertify:edit', CAP_ALLOW, $editorroleid, $syscontext);
        \role_assign($editorroleid, $user1->id, $catcontext->id);

        $data = [
            'contextid' => $catcontext->id,
            'sources' => ['manual' => []],
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setUser($user2);
        $this->assertSame('Active', \tool_mucertify\local\source\manual::render_status($certification, $source));
        $this->assertSame('Inactive', \tool_mucertify\local\source\manual::render_status($certification, null));

        $this->setUser($user1);
        $this->assertStringStartsWith('Active', \tool_mucertify\local\source\manual::render_status($certification, $source));
        $this->assertStringContainsString('"Update Manual assignment"', \tool_mucertify\local\source\manual::render_status($certification, $source));
        $this->assertStringStartsWith('Inactive', \tool_mucertify\local\source\manual::render_status($certification, null));
        $this->assertStringContainsString('"Update Manual assignment"', \tool_mucertify\local\source\manual::render_status($certification, null));
    }

    public function test_get_assigner(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $admin = get_admin();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);
        \tool_mucertify\local\source\manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->setUser(null);
        $result = \tool_mucertify\local\source\manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);

        $this->setUser($user2);
        $result = \tool_mucertify\local\source\manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($user2->id, $result->id);

        $this->setGuestUser();
        $result = \tool_mucertify\local\source\manual::get_assigner($certification, $source, $assignment);
        $this->assertSame($admin->id, $result->id);
    }
}
