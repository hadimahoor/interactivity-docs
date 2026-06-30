<?php

/**
 * Component: Post actions
 *
 * Like, save, and reading-time controls for a post card.
 * Bound via the WordPress Interactivity API.
 *
 * @package InteractivityDocs
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-block-template-part">

    <div class="wp-block-group is-horizontal is-content-justification-left is-layout-flex wp-container-core-group-is-layout-4-thesis wp-block-group-is-layout-flex"
        style="margin-top:var(--wp--preset--spacing--auto)">

        <!-- Like button -->
        <div class="wp-block-group is-layout-flex wp-container-core-group-is-layout-5-thesis wp-block-group-is-layout-flex">

            <a
                role="button"
                tabindex="0"
                aria-label="<?php esc_attr_e('Like', 'interactivity-docs'); ?>"
                data-wp-bind--aria-pressed="state.isActiveLike"
                data-wp-class--is-actived="state.isActiveLike"
                data-user="<?php echo esc_attr(get_current_user_id()); ?>"
                data-type="Like"
                data-wp-bind--data-id="context.item.data.id"
                data-wp-on--click="actions.handleLikeSave"
                data-wp-on--keydown="actions.handleKeydown"
                style="--active-color:var(--wp--preset--color--favorite);"
                class="arad-post-reaction-button gap-1.5 wp-block-arad-like-button-btn is-layout-flex wp-container-like-button-btn-is-layout-thesis wp-block-like-button-btn-is-layout-flex"
            >

                <div class="wp-block-outermost-icon-block items-justified-center">
                    <div
                        class="icon-container has-icon-color has-no-icon-fill-color has-icon-background-color has-shadcn-accent-background-color has-contrast-2-color"
                        style="background-color:var(--wp--preset--color--shadcn-accent);color:var(--wp--preset--color--contrast-2);width:34px;padding:8px;border-radius:99px"
                        aria-hidden="true"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true" focusable="false">
                            <path d="M19.4626 3.99415C16.7809 2.34923 14.4404 3.01211 13.0344 4.06801C12.4578 4.50096 12.1696 4.71743 12 4.71743C11.8304 4.71743 11.5422 4.50096 10.9656 4.06801C9.55962 3.01211 7.21909 2.34923 4.53744 3.99415C1.01807 6.15294 0.221721 13.2749 8.33953 19.2834C9.88572 20.4278 10.6588 21 12 21C13.3412 21 14.1143 20.4278 15.6605 19.2834C23.7783 13.2749 22.9819 6.15294 19.4626 3.99415Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>

                <span class="arad__count" data-wp-text="context.item.like_count" aria-live="polite"></span>

            </a>

        </div>

        <!-- Reading time and save button -->
        <div class="wp-block-group wp-container-content-58 is-content-justification-right is-layout-flex wp-container-core-group-is-layout-6-thesis wp-block-group-is-layout-flex">

            <!-- Reading time -->
            <div class="wp-block-arad-reading-time" aria-label="<?php esc_attr_e('Reading time', 'interactivity-docs'); ?>">
                <p class="reading-time">
                    <span data-wp-text="context.item.data.rTime"></span> <?php esc_html_e('min read', 'interactivity-docs'); ?>
                </p>
                <p class="reading-time-mobile" aria-hidden="true">
                    <span data-wp-text="context.item.data.rTime"></span> <?php esc_html_e('min', 'interactivity-docs'); ?>
                </p>
            </div>

            <!-- Save button -->
            <a
                role="button"
                tabindex="0"
                aria-label="<?php esc_attr_e('Save', 'interactivity-docs'); ?>"
                data-wp-bind--aria-pressed="state.isActiveSave"
                data-wp-class--is-actived="state.isActiveSave"
                data-user="<?php echo esc_attr(get_current_user_id()); ?>"
                data-type="Save"
                data-wp-bind--data-id="context.item.data.id"
                data-wp-on--click="actions.handleLikeSave"
                data-wp-on--keydown="actions.handleKeydown"
                style="--active-color:var(--wp--preset--color--shadcn-foreground);"
                class="arad-post-reaction-button gap-1.5 wp-block-arad-save-button-btn is-layout-flex wp-container-arad-save-button-btn-is-layout-10 wp-block-save-button-btn-is-layout-flex"
            >

                <div class="wp-block-outermost-icon-block items-justified-center">
                    <div
                        class="icon-container has-icon-color has-no-icon-fill-color has-icon-background-color has-shadcn-accent-background-color has-contrast-2-color"
                        style="background-color:var(--wp--preset--color--shadcn-accent);color:var(--wp--preset--color--contrast-2);width:34px;padding:8px;border-radius:99px"
                        aria-hidden="true"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke="currentColor" aria-hidden="true" focusable="false">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"></path>
                        </svg>
                    </div>
                </div>

            </a>

        </div>

    </div>

</div>
