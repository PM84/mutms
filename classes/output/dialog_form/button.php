<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mulib\output\dialog_form;

use renderer_base;
use moodle_url;

/**
 * Button that opens dialog form.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class button extends action {
    /** @var bool is this a primary button? */
    protected $primary;

    /**
     * Create button action.
     *
     * @param moodle_url $formurl
     * @param string $title
     * @param bool $primary
     */
    public function __construct(moodle_url $formurl, string $title, bool $primary = false) {
        parent::__construct($formurl, $title);
        $this->primary = $primary;
    }

    /**
     * Is this button primary?
     *
     * @return bool
     */
    public function is_primary(): bool {
        return $this->primary;
    }

    /**
     * Set button as primary.
     *
     * @param bool $value
     * @return void
     */
    public function set_primary(bool $value): void {
        $this->primary = $value;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $data = [
            'title' => $this->get_title(),
            'formurl' => $this->get_form_url()->out(false),
            'dialogname' => $this->get_dialog_name(),
            'aftersubmit' => $this->get_after_submit(),
            'class' => $this->get_class(),
            'dialogsize' => $this->get_dialog_size(),
            'disabled' => $this->is_disabled(),
            'legacyformtest' => (bool)$this->legacyformtest,
        ];
        if ($this->is_primary()) {
            $data['primary'] = 1;
        }

        $pixicon = $this->get_icon();
        if ($pixicon) {
            $data['icon'] = \core\output\icon_system::instance()->render_pix_icon($output, $pixicon);
        }

        return $data;
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'tool_mulib/dialog_form/button';
    }
}
