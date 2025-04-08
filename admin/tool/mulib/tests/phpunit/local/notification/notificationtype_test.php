<?php
// This file is part of Additional tools library for Moodle™.

namespace tool_mulib\phpunit\local\notification;

/**
 * Notification type base tests.
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
class notificationtype_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_component() {
        $this->assertSame('tool_mulib', \tool_mulib\local\notification\notificationtype::get_component());
    }

    public function test_get_classname() {
        $this->assertSame('notificationtype', \tool_mulib\local\notification\notificationtype::get_notificationtype());
    }

    public function test_format_subject() {
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_subject('', [])
        );
        $this->assertSame(
            'Test some subject {$a-&gt;def}',
            \tool_mulib\local\notification\notificationtype::format_subject('Test {$a->abc} subject {$a->def}', ['abc' => 'some', 'xyz' => 'opr'])
        );
        $this->assertSame(
            'Test some &lt;subject&gt;',
            \tool_mulib\local\notification\notificationtype::format_subject('Test {$a-&gt;abc} <subject>', ['abc' => 'some'])
        );
    }

    public function test_format_body() {
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_HTML, [])
        );
        $this->assertSame(
            '',
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_MARKDOWN, [])
        );
        $this->assertSame(
            "<span>great</span>\n\n{\$a-&gt;hmm}",
            \tool_mulib\local\notification\notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", \FORMAT_HTML, ['status' => 'great'])
        );
        $this->assertSame(
            "<p><span>great</span></p>\n\n<p>{\$a-&gt;hmm}</p>\n",
            \tool_mulib\local\notification\notificationtype::format_body("<span>{\$a->status}</span>\n\n{\$a->hmm}", \FORMAT_MARKDOWN, ['status' => 'great'])
        );

        try {
            \tool_mulib\local\notification\notificationtype::format_body('', \FORMAT_MOODLE, []);
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\coding_exception::class, $e);
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Unknown body format: 0',
                $e->getMessage()
            );
        }
    }
}
