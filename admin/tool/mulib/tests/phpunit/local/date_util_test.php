<?php
// This file is part of Additional tools library for Moodle™.

namespace phpunit\local;

/**
 * Date helper tests.
 *
 * @group       muTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\date_util
 */
class date_util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_date_util(): void {
        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-15T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+1 day)</sup>', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-17T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+2 days)</sup>', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-20T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM&ndash;3:00 PM<sup> (+5 days)</sup>', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-14T15:00:00'));
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), 0);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), null);
        $this->assertSame('15 August 2022&nbsp;&nbsp;&nbsp;11:00 AM', $result);

        $result = \tool_mulib\local\date_util::format_event_date(0, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        $result = \tool_mulib\local\date_util::format_event_date(null, strtotime('2022-08-15T15:00:00'));
        $this->assertSame('', $result);

        // Plan text decoding.
        $result = \tool_mulib\local\date_util::format_event_date(strtotime('2022-08-15T11:00:00'), strtotime('2022-08-16T15:00:00'));
        $result = strip_tags($result);
        $result = \core_text::entities_to_utf8($result);
        $this->assertSame('15 August 2022   11:00 AM–3:00 PM (+1 day)', $result);
    }
}