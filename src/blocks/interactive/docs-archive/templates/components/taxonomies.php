<?php

/**
 * Component: Taxonomy filters
 *
 * Renders taxonomy filter links for a given post type.
 * Skips the 'tag' taxonomy as it is handled separately.
 * @package InteractivityDocs
 * @var string $type Post type slug.
 */

use InteractivityDocs\Rest\Config\ApiConfig;

if (! defined('ABSPATH')) {
    exit;
}

$filters = ApiConfig::allowedFilters($type);

foreach ($filters as $key) :
    // Skip tag taxonomy — rendered elsewhere.
    if ('tag' === $key) {
        continue;
    }
    ?>
    <a
        data-tax="<?php echo esc_attr($key); ?>"
        data-wp-text="context.item.data.<?php echo esc_attr($key); ?>.name"
        data-wp-bind--href="context.item.data.<?php echo esc_attr($key); ?>.url"
        data-wp-style--background-color="context.item.data.<?php echo esc_attr($key); ?>.color"
        data-wp-style--color="context.item.data.<?php echo esc_attr($key); ?>.hover-color"
    ></a>

    <span class="wp-block-post-terms__separator"></span>
    <?php
endforeach;
