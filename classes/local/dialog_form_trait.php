<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mulib\local;

/**
 * Trait for legacy modal dialog forms.
 *
 * NOTE: this is an alternative to extending dialog_form.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait dialog_form_trait {
    /** @var bool true means do not use Javascript */
    private $islegacyajaxrequest = false;

    /**
     * Form constructor.
     *
     * @param string|null $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param null|array$attributes
     * @param bool $editable
     * @param mixed|null $ajaxformdata
     */
    final public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $ajaxformdata = null) {
        if (AJAX_SCRIPT) {
            // Make sure form element ids are randomised in case there are multiple forms on the page.
            $attributes = ['data-random-ids' => 1] + ($attributes ?: []);
        }

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);

        if (AJAX_SCRIPT) {
            $this->islegacyajaxrequest = true;
            // There might be two dialog forms initialised on one page.
            if (!defined('PREFERRED_RENDERER_TARGET')) {
                // Do the sam hacks as lib/ajax/service.php here to allow fragment rendering.
                define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);
                ob_start(); // We will be sending back only the form data.
            }
        }
    }

    /**
     * Replacement for redirect call after processing submitted form.
     *
     * @param $url
     * @param string|null $message
     * @param string $messagetype
     * @return void
     */
    final public function redirect_submitted($url, ?string $message = null, string $messagetype = \core\output\notification::NOTIFY_INFO): void {
        if ($this->islegacyajaxrequest) {
            if ($message) {
                // The notification will be shown after page reload or redirect.
                \core\notification::add($message, $messagetype);
            }
            // Started in constructor, ignore all output bfore form.
            ob_end_clean();
            if ($url instanceof \moodle_url) {
                $url = $url->out(false);
            }
            $data = [
                'dialog_form' => 'submitted',
                'redirecturl' => $url,
            ];
            echo json_encode(['data' => $data]);
            die;
        } else {
            redirect($url, $message, null, $messagetype);
        }
    }

    /**
     * Render form.
     *
     * @return string|void
     */
    final public function render() {
        global $PAGE;

        if (!$this->islegacyajaxrequest) {
            // Nothing special to do.
            return parent::render();
        }

        // Ignore all html markup before the form.
        ob_end_clean();

        // NOTE: this code uses the same hackery as fragment API in web services.
        $PAGE->start_collecting_javascript_requirements();
        ob_start();
        $this->display();
        $html = ob_get_contents();
        ob_end_clean();
        $jsfooter = $PAGE->requires->get_end_code();

        $data = [
            'dialog_form' => 'render',
            'html' => $html,
            'javascript' => $jsfooter,
            'pageheading' => $PAGE->heading,
            'pagetitle' => $PAGE->title,
        ];

        echo json_encode(['data' => $data]);
        die;
    }
}
