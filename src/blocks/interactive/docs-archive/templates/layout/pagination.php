<!-- ============================================
     Pagination Controls
     Displays page navigation when multiple pages exist.
     Hidden during loading state or in single-page mode.
============================================ -->

<!-- Active Pagination (visible when not loading and multiple pages exist) -->
<div 
    data-wp-bind--hidden="state.isHidePagination" 
    class="row"
    role="navigation"
    aria-label="نوار صفحه‌بندی"
>
    <!-- Previous Page Button -->
    <a 
        data-wp-bind--hidden="!context.pagination.prev"
        data-wp-on--click="actions.changePage"
        data-wp-bind--data-id="context.pagination.prev"
        data-wp-bind--aria-label="'صفحه ' + context.pagination.prev"
        class="page-numbers prev"
        role="button"
        tabindex="0"
    >
        قبلی
    </a>

    <!-- Page Number Links (rendered from pagination.items array) -->
    <template 
        data-wp-each="context.data.pagination.items"
        data-wp-each-key="context.item.item"
    >
        <a 
            data-wp-on--click="actions.changePage"
            data-wp-class--current="context.item.isCurrent"
            data-wp-bind--data-id="context.item.item"
            data-wp-bind--aria-label="'صفحه ' + context.item.item"
            data-wp-bind--aria-current="context.item.isCurrent ? 'page' : null"
            class="page-numbers"
            data-wp-text="context.item.item"
            role="button"
            tabindex="0"
        ></a>
    </template>

    <!-- Next Page Button -->
    <a 
        data-wp-bind--hidden="!context.pagination.next"
        data-wp-on--click="actions.changePage"
        data-wp-bind--data-id="context.pagination.next"
        data-wp-bind--aria-label="'صفحه ' + context.pagination.next"
        class="page-numbers next"
        role="button"
        tabindex="0"
    >
        بعدی
    </a>
</div>

<!-- Loading Skeleton (visible during data fetch) -->
<div 
    data-wp-bind--hidden="!context.ui.isLoading"
    class="skeleton-loader row"
    aria-hidden="true"
>
    <!-- Placeholder page number elements -->
    <a class="page-numbers" tabindex="-1"></a>
    <a class="page-numbers" tabindex="-1"></a>
    <a class="page-numbers" tabindex="-1"></a>
</div>
