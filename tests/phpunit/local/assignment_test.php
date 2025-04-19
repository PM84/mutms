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

use tool_mucertify\local\source\manual;
use tool_mucertify\local\assignment;
use tool_mulib\local\date_util;

/**
 * Certification assignment test.
 *
 * @group      muTMS
 * @package    tool_mucertify
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mucertify\local\assignment
 */
final class assignment_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_source_classes(): void {
        $classes = \tool_mucertify\local\assignment::get_source_classes();
        $this->assertIsArray($classes);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
        }
        $this->assertArrayHasKey('manual', $classes);
        $this->assertArrayHasKey('cohort', $classes);
        $this->assertArrayHasKey('selfassignment', $classes);
        $this->assertArrayHasKey('approval', $classes);
    }

    public function test_get_source_classname(): void {
        $this->assertSame(manual::class, assignment::get_source_classname('manual'));
        $this->assertSame(null, assignment::get_source_classname('xyz'));
    }

    public function test_get_source_names(): void {
        $names = \tool_mucertify\local\assignment::get_source_names();
        $this->assertSame('Manual assignment', $names['manual']);
        $this->assertSame('Automatic cohort assignment', $names['cohort']);
        $this->assertSame('Self assignment', $names['selfassignment']);
        $this->assertSame('Requests with approval', $names['approval']);
    }

    public function test_get_status_html(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);
        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $period = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Not certified', $status);

        $certification->archived = '1';
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
        $certification->archived = '0';

        $assignment->archived = '1';
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
        $assignment->archived = '0';

        $period->timecertified = $now - HOURSECS;
        $period->timefrom = $now - DAYSECS;
        $period->timeuntil = $now + DAYSECS;
        $DB->update_record('tool_mucertify_period', $period);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Valid', $status);

        $period->timerevoked = $now;
        $DB->update_record('tool_mucertify_period', $period);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Not certified', $status);

        $period->timerevoked = null;
        $period->timecertified = $now - DAYSECS;
        $period->timefrom = $now - WEEKSECS;
        $period->timeuntil = $now - HOURSECS;
        $DB->update_record('tool_mucertify_period', $period);
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Expired', $status);

        $assignment->timecertifiedtemp = $now + DAYSECS;
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Temporary valid', $status);

        $assignment->timecertifiedtemp = $now - 1;
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Expired', $status);

        $assignment->archived = '1';
        $status = \tool_mucertify\local\assignment::get_status_html($certification, $assignment);
        $this->assertStringContainsString('Archived', $status);
    }

    public function test_sync_current_status(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);
        manual::assign_users($certification->id, $source->id, [$user1->id]);

        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        \tool_mucertify\local\assignment::sync_current_status($assignment);
    }

    public function test_fix_assignment_sources(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);
        manual::assign_users($certification->id, $source->id, [$user1->id]);

        \tool_mucertify\local\assignment::fix_assignment_sources(null, null);
        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, null);
        \tool_mucertify\local\assignment::fix_assignment_sources($certification->id, $user1->id);
        \tool_mucertify\local\assignment::fix_assignment_sources(null, $user1->id);
    }

    public function test_has_active_assignments(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $this->assertTrue(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'archived' => 1]);
        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'archived' => 0]);
        $certification = \tool_mucertify\local\certification::archive($certification->id);
        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));
    }

    public function test_get_until_html(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame('Not set', \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + DAYSECS]);
        $this->assertSame(userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame('Not set', \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + WEEKSECS]);
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(userdate($assignment->timecertifiedtemp, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => (string)($now + 7000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::add((object)$data);
        $this->assertSame(userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $dateoverrides = [
            'id' => $period2->id,
            'timecertified' => (string)($now + 1234),
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(userdate($period2->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));

        $dateoverrides = [
            'id' => $period2->id,
            'timerevoked' => $now,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(userdate($period1->timeuntil, get_string('strftimedatetimeshort')),
            \tool_mucertify\local\assignment::get_until_html($certification, $assignment));
    }

    public function test_fix_caches(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $now = time();

        manual::assign_users($certification->id, $source->id, [$user2->id]);
        $periodx = $DB->get_record('tool_mucertify_period', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $periodx->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $periodx = \tool_mucertify\local\period::override_dates((object)$dateoverrides);

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);

        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => null,
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::add((object)$data);
        $data = [
            'assignmentid' => $assignment1->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 500),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 8000),
            'timeuntil' => (string)($now + 9000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period3 = \tool_mucertify\local\period::add((object)$data);

        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 300, ['id' => $period1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 200, ['id' => $period2->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame((string)date_util::TIMESTAMP_FOREVER, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', $now - 100, ['id' => $period3->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame((string)date_util::TIMESTAMP_FOREVER, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timerevoked', $now, ['id' => $period2->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now - 100, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now - 100), $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + YEARSECS, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + YEARSECS), $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($assignment1->timecertifiedtemp, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', null, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period3->timeuntil, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_period', 'timecertified', null, ['id' => $period3->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment1->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment1->timecertifieduntil);

        $DB->delete_records('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecreated', $now - 10, ['id' => $assignment1->id]);
        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + WEEKSECS, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + WEEKSECS), $assignment1->timecertifiedtemp);
        $this->assertSame((string)($now - 10), $assignment1->timecertifiedfrom);
        $this->assertSame($assignment1->timecertifiedtemp, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecreated', $now - 10, ['id' => $assignment1->id]);
        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', $now + 10, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame((string)($now + 10), $assignment1->timecertifiedtemp);
        $this->assertSame((string)($assignment1->timecertifiedtemp - DAYSECS), $assignment1->timecertifiedfrom);
        $this->assertSame($assignment1->timecertifiedtemp, $assignment1->timecertifieduntil);

        $DB->set_field('tool_mucertify_assignment', 'timecertifiedtemp', null, ['id' => $assignment1->id]);
        assignment::fix_caches($assignment1->id);
        $assignment1 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment1->timecertifiedtemp);
        $this->assertSame(null, $assignment1->timecertifiedfrom);
        $this->assertSame(null, $assignment1->timecertifieduntil);

        $assignment2 = $DB->get_record('tool_mucertify_assignment', ['userid' => $user2->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment2->timecertifiedtemp);
        $this->assertSame((string)($now + 1000), $assignment2->timecertifiedfrom);
        $this->assertSame((string)($now + 5000), $assignment2->timecertifieduntil);
    }

    public function test_fix_caches_indirect(): void {
        global $DB;

        /** @var \tool_mucertify_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mucertify');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $programgenerator->create_program();
        $user1 = $this->getDataGenerator()->create_user();

        $now = time();

        $data = [
            'sources' => ['manual' => []],
            'programid1' => $program1->id,
        ];
        $certification = $generator->create_certification($data);
        $source = $DB->get_record('tool_mucertify_source',
            ['type' => 'manual', 'certificationid' => $certification->id], '*', MUST_EXIST);

        $this->assertFalse(\tool_mucertify\local\assignment::has_active_assignments($user1->id));

        manual::assign_users($certification->id, $source->id, [$user1->id]);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + DAYSECS]);
        $this->assertSame((string)($now), $assignment->timecertifiedfrom);
        $this->assertSame((string)($now + DAYSECS), $assignment->timecertifieduntil);

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $period1 = $DB->get_record('tool_mucertify_period', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => (string)$now,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $this->assertSame(null, $assignment->timecertifiedfrom);
        $this->assertSame(null, $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame($dateoverrides['timeuntil'], $assignment->timecertifieduntil);

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => $now + WEEKSECS]);
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame((string)($now + WEEKSECS), $assignment->timecertifieduntil);

        $assignment = \tool_mucertify\local\source\base::update_assignment((object)['id' => $assignment->id, 'timecertifiedtemp' => null]);
        $dateoverrides = [
            'id' => $period1->id,
            'timewindowstart' => (string)($now + 1500),
            'timewindowdue' => (string)($now + 2000),
            'timewindowend' => (string)($now + 3000),
            'timefrom' => (string)($now + 1000),
            'timeuntil' => (string)($now + 5000),
            'timecertified' => (string)$now,
            'timerevoked' => null,
        ];
        $period1 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $data = [
            'assignmentid' => $assignment->id,
            'programid' => $program1->id,
            'timewindowstart' => (string)($now + 4000),
            'timewindowdue' => null,
            'timewindowend' => null,
            'timefrom' => (string)($now + 6000),
            'timeuntil' => (string)($now + 7000),
            'timecertified' => null,
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::add((object)$data);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($dateoverrides['timefrom'], $assignment->timecertifiedfrom);
        $this->assertSame($dateoverrides['timeuntil'], $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period2->id,
            'timecertified' => (string)($now + 1234),
            'timerevoked' => null,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame($period1->timefrom, $assignment->timecertifiedfrom);
        $this->assertSame($period2->timeuntil, $assignment->timecertifieduntil);

        $dateoverrides = [
            'id' => $period2->id,
            'timerevoked' => $now,
        ];
        $period2 = \tool_mucertify\local\period::override_dates((object)$dateoverrides);
        $assignment = $DB->get_record('tool_mucertify_assignment', ['userid' => $user1->id, 'certificationid' => $certification->id], '*', MUST_EXIST);
        $this->assertSame(null, $assignment->timecertifiedtemp);
        $this->assertSame($period1->timefrom, $assignment->timecertifiedfrom);
        $this->assertSame($period1->timeuntil, $assignment->timecertifieduntil);
    }
}
