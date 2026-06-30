<?php

/**
 * Default Card Template
 *
 * Default card layout for posts and custom post types. Renders the
 * taxonomy pill, title, author avatars and post actions, with an
 * optional book cover for the 'book' entity type.
 *
 * @package InteractivityDocs
 *
 * @var string $blockPath Block path used to locate templates.
 * @var string $type      Entity type (e.g. 'book', 'thesis').
 */

use InteractivityDocs\Support\TemplateLoader;

defined('ABSPATH') || exit;

$blockPath = $blockPath ?? 'interactive/docs-archive';
$type = $type ?? get_post_type();
?>

<!-- Card wrapper with post metadata classes -->
<li class="wp-block-post post type-thesis tag-theme">

    <!-- Main columns container with border and background -->
    <div class="wp-block-columns has-border-color has-shadcn-border-border-color has-shadcn-background-background-color has-background is-layout-flex wp-container-core-columns-is-layout-thesis wp-block-columns-is-layout-flex">

        <!-- Single column taking full width -->
        <div class="wp-block-column is-layout-flow wp-container-core-column-is-layout-thesis wp-block-column-is-layout-flow" style="flex-basis: 100%">

            <!-- Content wrapper with vertical layout and stretch justification -->
            <div class="wp-block-group wp-container-content-thesis is-vertical is-content-justification-stretch is-layout-flex wp-container-core-group-is-layout-1-thesis wp-block-group-is-layout-flex">

                <!-- Inner group for taxonomy, title, and authors -->
                <div class="wp-block-group is-vertical is-layout-flex wp-container-core-group-is-layout-2-thesis wp-block-group-is-layout-flex">

                    <!-- Taxonomy / category pill -->
                    <div class="taxonomy-category is-style-pill wp-block-post-terms">
                        <?php echo TemplateLoader::render($blockPath, 'components/taxonomies', ['type' => $type]); ?>
                    </div>

                    <!-- Post title with decorative icon and interactive link -->
                    <h2 class="is-style-line-clamp-2 wp-block-post-title has-base-font-size">
                        <!-- Decorative document icon -->
                        <svg width="25" height="26" viewBox="0 0 46 61" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                            <path d="M45.3843 8C45.3843 3.89844 42.103 0.5 37.8843 0.5H7.88428C3.78271 0.5 0.384277 3.89844 0.384277 8V53C0.384277 57.2188 3.78271 60.5 7.88428 60.5H37.8843C42.103 60.5 45.3843 57.2188 45.3843 53V8ZM37.8843 17.375C37.8843 18.4297 37.064 19.25 36.0093 19.25H9.75928C8.82178 19.25 7.88428 18.4297 7.88428 17.375C7.88428 16.4375 8.82178 15.5 9.75928 15.5H36.0093C37.064 15.5 37.8843 16.4375 37.8843 17.375ZM36.0093 26.75C37.064 26.75 37.8843 27.6875 37.8843 28.625C37.8843 29.6797 37.064 30.5 36.0093 30.5H9.75928C8.82178 30.5 7.88428 29.6797 7.88428 28.625C7.88428 27.6875 8.82178 26.75 9.75928 26.75H36.0093ZM36.0093 38C37.064 38 37.8843 38.9375 37.8843 39.875C37.8843 40.9297 37.064 41.75 36.0093 41.75H21.0093C20.0718 41.75 19.1343 40.9297 19.1343 39.875C19.1343 38.9375 20.0718 38 21.0093 38H36.0093Z" fill="hsl(220 8.9% 46.1%)"/>
                        </svg>
                        <!-- Interactive title link bound to context data -->
                        <a data-wp-text="context.item.data.title" data-wp-bind--href="context.item.data.slug"></a>
                    </h2>

                    <!-- Authors section with avatar loop -->
                    <div class="wp-block-group is-layout-flex wp-container-core-group-is-layout-3-thesis wp-block-group-is-layout-flex">
                        <span><?php esc_html_e('Author', 'interactivity-docs'); ?> :</span>
                        <!-- Loop through authors using the interactive API -->
                        <template data-wp-each--authori="context.item.data.authors">
                            <div class="wp-block-avatar">
                                <a class="wp-block-avatar__link" data-wp-bind--href="context.authori.url">
                                    <img height="34" width="34" alt="" class="avatar avatar-28 photo wp-block-avatar__image" data-wp-bind--src="context.authori.image" />
                                </a>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Post actions (like, bookmark, share, etc.) -->
                <?php echo TemplateLoader::render($blockPath, 'components/post-actions'); ?>

            </div>
        </div>

        <!-- Optional book cover for the 'book' entity type -->
        <?php if ($type === 'book') : ?>
            <?php echo TemplateLoader::render($blockPath, 'components/book-cover'); ?>
        <?php endif; ?>

    </div>
</li>
