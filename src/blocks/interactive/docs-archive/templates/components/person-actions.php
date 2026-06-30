<?php

/**
 * Component: Person actions
 *
 * Like and save controls for a person card.
 *
 * @package InteractivityDocs
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wp-block-group is-layout-flex">

 <a
     role="button"
      tabindex="0"
        aria-label="<?php esc_attr_e('Like', 'interactivity-docs'); ?>"
       data-wp-bind--aria-pressed="state.isActiveLike"
        data-wp-class--is-actived="state.isActiveLike"
     data-type="Like"
       data-wp-bind--data-id="context.item.data.id"
       data-wp-on--click="actions.handleLikeSave"
     data-wp-on--keydown="actions.handleKeydown"
    >
      <span data-wp-text="context.item.like_count" aria-live="polite"></span>
    </a>

  <a
     role="button"
      tabindex="0"
        aria-label="<?php esc_attr_e('Save', 'interactivity-docs'); ?>"
       data-wp-bind--aria-pressed="state.isActiveSave"
        data-wp-class--is-actived="state.isActiveSave"
     data-type="Save"
       data-wp-bind--data-id="context.item.data.id"
       data-wp-on--click="actions.handleLikeSave"
     data-wp-on--keydown="actions.handleKeydown"
    ></a>
</div>
