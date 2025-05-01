<?php
// This file is part of Additional tools library for Moodle™.
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

namespace tool_mulib\output\dialog_form;

use renderer_base;
use moodle_url;

/**
 * Action icon that opens dialog form.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class icon extends action {
    /**
     * Create icon action.
     *
     * @param moodle_url $formurl
     * @param string $title
     * @param string $pix
     * @param string $pixcomponent
     */
    public function __construct(moodle_url $formurl, string $title, string $pix, string $pixcomponent = 'moodle') {
        parent::__construct($formurl, $title);
        $this->pixicon = new \core\output\pix_icon($pix, $title, $pixcomponent, ['aria-hidden' => 'true']);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $data = [
            'icon' => \core\output\icon_system::instance()->render_pix_icon($output, $this->get_icon()),
            'title' => $this->get_title(),
            'formurl' => $this->get_form_url()->out(false),
            'dialogname' => $this->get_dialog_name(),
            'aftersubmit' => $this->get_after_submit(),
            'class' => $this->get_class(),
            'dialogsize' => $this->get_dialog_size(),
            'disabled' => $this->is_disabled(),
            'legacyformtest' => (bool)$this->legacyformtest,
        ];

        return $data;
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'tool_mulib/dialog_form/icon';
    }
}
