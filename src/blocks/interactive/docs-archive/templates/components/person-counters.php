<?php

/**
 * Component: Person counters
 *
 * Paper and book counts for a person card.
 *
 * @package InteractivityDocs
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="counter-wrapper">
    <div>
        <span data-wp-text="context.item.paper_count" aria-label="<?php esc_attr_e('Papers', 'interactivity-docs'); ?>"></span>
  </div>
 <div>
        <span data-wp-text="context.item.book_count" aria-label="<?php esc_attr_e('Books', 'interactivity-docs'); ?>"></span>
    </div>
</div>
