<?php

/**
 * Documents List Template
 *
 * Renders a responsive grid of items based on the entity type
 * (person, book, or default), followed by skeleton loaders for
 * the loading state.
 *
 * @package InteractivityDocs
 *
 * @var string $type      Entity type ('person', 'book', or default).
 * @var int    $columns   Number of grid columns.
 * @var string $blockPath Block path used to locate templates.
 */

use InteractivityDocs\Support\TemplateLoader;

defined('ABSPATH') || exit;

// Layout classes differ between person (term template) and post types.
$classByType = $type === 'person'
    ? 'wp-block-arad-term-template wp-container-arad-term-template-is-layout-1 wp-block-term-template-is-layout-grid'
    : 'wp-block-post-template wp-container-core-post-template-is-layout-thesis wp-block-post-template-is-layout-grid';

// Pick the card template that matches the entity type.
$cardTemplate = 'cards/card-default';

if ($type === 'person') {
    $cardTemplate = 'cards/card-person';
} elseif ($type === 'book') {
    $cardTemplate = 'cards/card-book';
}
?>

<ul
    class="is-layout-grid columns-<?php echo esc_attr($columns . ' ' . $classByType); ?>"
    data-wp-bind--hidden="context.ui.isLoading"
>
    <template data-wp-each="context.data.items">
        <?php echo TemplateLoader::render($blockPath, $cardTemplate, ['type' => $type]); ?>
    </template>
</ul>

<?php
// Skeleton loader shown while items are being fetched.
echo TemplateLoader::render($blockPath, 'layout/skeleton');
