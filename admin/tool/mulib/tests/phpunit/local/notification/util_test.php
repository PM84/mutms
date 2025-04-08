<?php
// This file is part of Additional tools library for Moodle™.

namespace tool_mulib\phpunit\local\notification;

/**
 * Notification util tests.
 *
 * @group       muTMS
 * @package     tool_mulib
 * @copyright   2023 Open LMS
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_mulib\local\notification\util
 */
class util_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_manager_classname() {
        // TODO
    }

    public function test_notification_create() {
        // TODO
    }

    public function test_notification_update() {
        // TODO
    }

    public function test_notification_delete() {
        // TODO
    }

    public function test_replace_placeholders() {
        $this->assertSame('abc', \tool_mulib\local\notification\util::replace_placeholders('abc', ['opr' => 'OPR']));

        $def = function() {
            return 'DEF';
        };
        $return = \tool_mulib\local\notification\util::replace_placeholders('abc {$a->opr} ({$a-&gt;def}) {$a}', ['opr' => 'OPR', 'abc' => 'ABC', 'def' => $def]);
        $this->assertSame('abc OPR (DEF) {$a}', $return);
    }

    public function test_filter_multilang() {
        $text = '<span lang="en" class="multilang">your_content_in English</span>
                <span lang="de" class="multilang">your_content_in_German_here</span>';
        $onelang = 'your_content_in English';

        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        \filter_set_global_state('multilang', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));
    }

    public function test_filter_multilang2() {
        if (!\get_config('filter_multilang2', 'version')) {
            $this->markTestSkipped('Test requires filter_multilang2 plugin');
        }

        $text = '{mlang en}your_content_in English{mlang}
{mlang other}your_content_in_German_here{mlang}';
        $onelang = 'your_content_in English
';

        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));

        // There does not seem to be a better way to purge the ad-hoc cache from filter_get_globally_enabled().
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'core_filter', 'global_filters');

        \filter_set_global_state('multilang2', TEXTFILTER_ON);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang2', TEXTFILTER_OFF);
        $cache->purge();
        $this->assertSame($onelang, \tool_mulib\local\notification\util::filter_multilang($text, false));

        \filter_set_global_state('multilang2', TEXTFILTER_DISABLED);
        $cache->purge();
        $this->assertSame($text, \tool_mulib\local\notification\util::filter_multilang($text, false));
    }

    public function test_notification_import() {
        // Invalid data tests only here, real data tests in:
        // \tool_muprog\local\notification_manager_test::test_notification_util_notification_import().

        try {
            \tool_mulib\local\notification\util::notification_import((object)['component' => 'fdjhkjsdhkfds', 'instanceid' => 2, 'frominstance' => 3], []);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $ex);
        }
    }
}
