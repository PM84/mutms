<?php
// This file is part of Additional tools library for Moodle™.

namespace tool_mulib\output\dialog_form;

use renderer_base;
use moodle_url;

/**
 * Link that opens dialog form.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class link extends action {
    /**
     * Create link with optional icon.
     *
     * @param moodle_url $formurl
     * @param string $title
     * @param string|null $pix
     * @param string $pixcomponent
     */
    public function __construct(moodle_url $formurl, string $title, ?string $pix = null, string $pixcomponent = 'moodle') {
        parent::__construct($formurl, $title);
        if ($pix !== null) {
            $this->pixicon = new \core\output\pix_icon($pix, $title, $pixcomponent, ['aria-hidden' => 'true']);
        }
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
            'uniqid' => uniqid(),
            'disabled' => $this->is_disabled(),
            'legacyformtest' => (bool)$this->legacyformtest,
            'icon' => '',
        ];

        $icon = $this->get_icon();
        if ($icon) {
            $data['icon'] = \core\output\icon_system::instance()->render_pix_icon($output, $icon);
        }

        return $data;
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'tool_mulib/dialog_form/link';
    }

    /**
     * Create reportbuilder action.
     *
     * @param array $attributes action attributes
     * @return \core_reportbuilder\local\report\action
     */
    public function create_report_action(array $attributes = []): \core_reportbuilder\local\report\action {
        $dialogname = json_encode($this->get_dialog_name());
        $aftersubmit = json_encode($this->get_after_submit());
        $size = json_encode($this->get_dialog_size());

        $attributes['onclick'] = "
let link = this;
require([
    'tool_mulib/dialog_form',
], function(DialogForm) {
    let form = new DialogForm({
        modalConfig: {title: $dialogname},
        triggerElement: link,
        formUrl: link.href,
        afterSubmit: $aftersubmit,
        size: $size,
    });
    
});
return false;";

        $action = new \core_reportbuilder\local\report\action(
            $this->get_form_url(),
            $this->get_icon(),
            $attributes
        );

        return $action;
    }
}
