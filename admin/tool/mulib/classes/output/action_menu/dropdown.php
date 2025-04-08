<?php
// This file is part of Additional tools library for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mulib\output\action_menu;

/**
 * Action menu dropdown.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dropdown implements \renderable, \core\output\named_templatable {
    /** @var array $items links, dividers or custom html fragments */
    protected $items = [];
    /** @var string */
    protected $title;

    /**
     * Constructor.
     *
     * @param string $title
     */
    public function __construct(string $title) {
        $this->title = $title;
    }

    /**
     * Add standard link item to dropdown.
     *
     * @param string $label
     * @param \moodle_url $url
     */
    public function add_item(string $label, \moodle_url $url): void {
        $this->items[] = ['label' => $label, 'url' => $url->out(false)];
    }

    /**
     * Add divider element.
     */
    public function add_divider(): void {
        $this->items[] = ['divider' => true];
    }

    /**
     * Add link that opens dialog_form.
     *
     * @param \tool_mulib\output\dialog_form\link $link
     */
    public function add_dialog_form(\tool_mulib\output\dialog_form\link $link): void {
        global $OUTPUT;
        $oldclass = $link->get_class();
        $link->set_class('dropdown-item');
        $this->items[] = ['customhtml' => $OUTPUT->render($link)];
        $link->set_class($oldclass);
    }

    /**
     * Are there any items?
     *
     * @return bool
     */
    public function has_items(): bool {
        return !empty($this->items);
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        return [
            'title' => $this->title,
            'items' => $this->items,
        ];
    }

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'tool_mulib/action_menu/dropdown';
    }
}
