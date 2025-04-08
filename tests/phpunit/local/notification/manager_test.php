<?php
// This file is part of Additional tools library for Moodle™.

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
class manager_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_component() {
        $this->assertSame('tool_mulib', \tool_mulib\local\notification\manager::get_component());
    }

    public function test_is_import_supported() {
        $this->assertFalse(\tool_mulib\local\notification\manager::is_import_supported());
    }
}
