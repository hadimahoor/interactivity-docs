<?php

/**
 * Entity Filters Template
 *
 * Renders the search filters bar including:
 * - Selected filters summary (pill badges)
 * - Sort dropdown
 * - Dynamic taxonomy filter dropdowns
 * - Skeleton loaders for the loading state
 *
 * @var string $type  Entity type (e.g. 'person', 'book').
 * @var string $title Page/query title to display.
 */

use InteractivityDocs\Rest\Config\ApiConfig;

defined('ABSPATH') || exit;

$filters = ApiConfig::allowedFilters($type);
?>

<!-- SVG sprite definitions -->
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
    <defs>
        <symbol id="icon-sort" viewBox="0 0 24 24" width="24" height="24" color="#000000" fill="none">
            <path d="M15 19L15 6.65856C15 5.65277 15 5.14987 15.3087 5.02472C15.6173 4.89956 15.9806 5.25517 16.7071 5.96637L19 8.21091" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 5L9 17.3414C9 18.3472 9 18.8501 8.69134 18.9753C8.38268 19.1004 8.01942 18.7448 7.29289 18.0336L5 15.7891" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
        <symbol id="icon-chevron" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24">
            <path d="m19.5 8.25-7.5 7.5-7.5-7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </symbol>
    </defs>
</svg>


<!-- Selected Filters -->
<ul id="searchSelectedFilters">
    <?php foreach ($filters as $key) : ?>
        <li
            data-wp-bind--hidden="context.ui.isLoading"
            data-wp-class--active="state.selector.<?php echo esc_attr($key); ?>FilterHide"
        >
            <a
                data-tax="<?php echo esc_attr($key); ?>"
                data-wp-on--click="actions.removeFilter"
                data-wp-text="context.selectedFilters.map.<?php echo esc_attr($key); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Remove %s filter', 'interactivity-docs'), $key)); ?>"
            ></a>
            <span aria-hidden="true">&times;</span>
        </li>

        <li
            data-wp-bind--hidden="!context.ui.isLoading"
            data-wp-class--active="state.selector.<?php echo esc_attr($key); ?>FilterHide"
            class="skeleton-loader"
            aria-hidden="true"
        ></li>
    <?php endforeach; ?>
</ul>


<!-- Filter Bar -->
<div class="entity-filter-wrapper">
    <div class="entity-filter-box">

        <!-- Title -->
        <h1
            data-wp-bind--hidden="context.ui.isLoading"
            class="entity-title"
        >
            <span class="entity-counter" data-wp-text="context.data.count" data-wp-bind--hidden="state.isHide"></span><?php echo esc_html($title); ?>
        </h1>

        <h1
            data-wp-bind--hidden="!context.ui.isLoading"
            class="entity-title skeleton-loader"
            aria-hidden="true"
        ></h1>


        <!-- Selectors -->
        <div
            data-wp-bind--hidden="context.ui.isLoading"
            class="selectors-box"
        >

            <!-- Sort Dropdown -->
            <div
                data-tax="sort"
                class="filter-dropdown"
                data-wp-on--focusout="actions.handleMenuFocusout"
                data-wp-on--keydown="actions.handleMenuKeydown"
                data-wp-watch="callbacks.onWatchMenu"
                data-wp-class--is-open="state.isMenuOpen.sort"
            >
                <button
                    data-tax="sort"
                    type="button"
                    class="filter-trigger"
                    data-wp-on--click="actions.toggleMenuOnClick"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                >
                    <svg class="icon-sort hide-mobile" aria-hidden="true"><use href="#icon-sort"/></svg>
                    <span><?php esc_html_e('Sort', 'interactivity-docs'); ?></span>
                    <svg class="icon-chevron" aria-hidden="true"><use href="#icon-chevron"/></svg>
                </button>

                <div
                    data-tax="sort"
                    class="filter-menu"
                    data-wp-on-async--focus="actions.openMenuOnFocus"
                    role="listbox"
                    tabindex="-1"
                >
                    <ul>
                        <li role="option">
                            <a
                                data-tax="sort"
                                data-val="title"
                                data-wp-on--click="actions.selectFilter"
                                data-wp-class--is-active="state.isActiveTitle"
                                aria-label="<?php esc_attr_e('Sort by name', 'interactivity-docs'); ?>"
                            >
                                <?php esc_html_e('Name', 'interactivity-docs'); ?>
                            </a>
                        </li>

                        <?php if ($type === 'person') : ?>
                            <li role="option">
                                <a
                                    data-tax="sort"
                                    data-val="paper_count"
                                    data-wp-on--click="actions.selectFilter"
                                    data-wp-class--is-active="state.isActivePaperCount"
                                    aria-label="<?php esc_attr_e('Sort by paper count', 'interactivity-docs'); ?>"
                                >
                                    <?php esc_html_e('Paper Count', 'interactivity-docs'); ?>
                                </a>
                            </li>

                            <li role="option">
                                <a
                                    data-tax="sort"
                                    data-val="book_count"
                                    data-wp-on--click="actions.selectFilter"
                                    data-wp-class--is-active="state.isActiveBookCount"
                                    aria-label="<?php esc_attr_e('Sort by book count', 'interactivity-docs'); ?>"
                                >
                                    <?php esc_html_e('Book Count', 'interactivity-docs'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <p class="separator" data-wp-bind--hidden="state.separatorHide">/</p>


            <!-- Dynamic Filters -->
            <?php foreach ($filters as $key) : ?>
                <div
                    data-tax="<?php echo esc_attr($key); ?>"
                    data-wp-bind--hidden="state.selector.<?php echo esc_attr($key); ?>SelectorHide"
                    class="filter-dropdown"
                    data-wp-on--focusout="actions.handleMenuFocusout"
                    data-wp-on--keydown="actions.handleMenuKeydown"
                    data-wp-watch="callbacks.onWatchMenu"
                    data-wp-class--is-open="state.isMenuOpen.<?php echo esc_attr($key); ?>"
                >
                    <button
                        data-tax="<?php echo esc_attr($key); ?>"
                        type="button"
                        class="filter-trigger"
                        data-wp-on--click="actions.toggleMenuOnClick"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <svg class="icon-sort hide-mobile" aria-hidden="true"><use href="#icon-sort"/></svg>
                        <span><?php echo esc_html($key); ?></span>
                        <svg class="icon-chevron" aria-hidden="true"><use href="#icon-chevron"/></svg>
                    </button>

                    <div
                        data-tax="<?php echo esc_attr($key); ?>"
                        class="filter-menu"
                        data-wp-on-async--focus="actions.openMenuOnFocus"
                        role="listbox"
                        tabindex="-1"
                    >
                        <ul>
                            <template data-wp-each--selector="context.data.meta.<?php echo esc_attr($key); ?>">
                                <li role="option">
                                    <a
                                        data-wp-bind--id="context.selector.count"
                                        data-wp-bind--data-count="context.selector.count"
                                        data-tax="<?php echo esc_attr($key); ?>"
                                        data-wp-bind--data-val="context.selector.value"
                                        data-wp-on--click="actions.selectFilter"
                                    >
                                        <span class="count" data-wp-text="context.selector.count"></span>
                                        <span class="label" data-wp-text="context.selector.value"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>


        <!-- Skeleton Selectors -->
        <div
            data-wp-bind--hidden="!context.ui.isLoading"
            class="selectors-box skeleton-loader"
            aria-hidden="true"
        >
            <div class="filter-dropdown"></div>
            <p class="separator">/</p>
            <div class="filter-dropdown"></div>
            <div class="filter-dropdown"></div>
        </div>

    </div>
</div>
