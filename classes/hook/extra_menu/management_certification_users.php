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

namespace tool_mucertify\hook\extra_menu;

/**
 * Extra menu in user allocation tab for certification.
 *
 * @package    tool_mucertify
 * @copyright  2024 Open LMS
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Additions to "Users actions" extra menu in Users tab in certification.')]
final class management_certification_users extends \tool_mulib\output\action_menu\dropdown {
    /** @var \stdClass certification record */
    protected $certification;

    /**
     * Create hook for extra menu.
     *
     * @param \stdClass $certification
     */
    public function __construct(\stdClass $certification) {
        parent::__construct(get_string('extra_menu_management_certification_users', 'tool_mucertify'));
        $this->certification = $certification;
    }

    /**
     * Returns certification of user allocation page.
     *
     * @return \stdClass certification record
     */
    public function get_certification(): \stdClass {
        return $this->certification;
    }
}
