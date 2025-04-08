<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mulib\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Base class for legacy modal dialog forms.
 *
 * @package     tool_mulib
 * @copyright   2022 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class dialog_form extends \moodleform {
    use dialog_form_trait;
}
