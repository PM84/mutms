<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mulib\phpunit\local\notification;

/**
 * Notification manager base tests.
 *
 * @group       muTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\notification\manager
 */
final class manager_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * @covers ::get_component
     */
    public function test_get_component(): void {
        $this->assertSame('tool_mulib', \tool_mulib\local\notification\manager::get_component());
    }

    /**
     * @covers ::is_import_supported
     */
    public function test_is_import_supported(): void {
        $this->assertFalse(\tool_mulib\local\notification\manager::is_import_supported());
    }
}
