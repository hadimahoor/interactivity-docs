<?php

/**
 * Person Card Item
 *
 * Renders a single person card with image, title, roles and counters.
 *
 * @package InteractivityDocs
 *
 * @var string $blockPath Block path used to locate templates.
 */

use InteractivityDocs\Support\TemplateLoader;

defined('ABSPATH') || exit;
?>

<li class="arad-term-item">

    <!-- Linked group wrapping the whole card -->
    <div class="wp-block-group is-linked">

        <!-- Overlay link to the person permalink -->
        <a class="wp-block-group__link"
           data-wp-bind--href="context.item.data.permalink"></a>

        <!-- Card body -->
        <div class="wp-block-group has-background is-layout-flex arad-card-person">

            <!-- Person quick actions (follow, share, etc.) -->
            <?php echo TemplateLoader::render($blockPath, 'components/person-actions'); ?>

            <!-- Avatar -->
            <div class="wp-block-group has-background avatar-circle">
                <figure>
                    <img alt="" data-wp-bind--src="context.item.data.image" />
                </figure>
            </div>

            <!-- Person name -->
            <h2 data-wp-text="context.item.data.title"></h2>

            <!-- Roles loop -->
            <template data-wp-each--roles="context.item.data.role">
                <a class="role-link" data-wp-bind--href="context.roles.url">
                    <span data-wp-text="context.roles.name"></span>
                </a>
            </template>

            <!-- Person counters (papers, books, etc.) -->
            <?php echo TemplateLoader::render($blockPath, 'components/person-counters'); ?>

        </div>

    </div>
</li>
