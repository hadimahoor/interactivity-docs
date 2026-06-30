<?php

declare(strict_types=1);

use InteractivityDocs\Repository\RepositoryFactory;
use InteractivityDocs\Rest\Config\ApiConfig;
use InteractivityDocs\Support\UIHelpers;
use InteractivityDocs\Support\TemplateLoader;

if (!defined('ABSPATH')) {
    exit;
}

$blockPath = 'interactive/docs-archive';

/*
|--------------------------------------------------------------------------
| Attribute → API Key Map  (camelCase → snake_case)
|--------------------------------------------------------------------------
| Local (not a global const) so multiple blocks on one page are safe.
*/

$attributeToApiMap = [
    'paperType' => 'paper_type',
    'bookType'  => 'book_type',
];

/*
|--------------------------------------------------------------------------
| Attributes
|--------------------------------------------------------------------------
*/

$attributes = wp_parse_args($attributes ?? [], [
    'type'         => 'paper',
    'itemsPerPage' => 12,
    'currentPage'  => 1,
    'sort'         => 'latest',
    'status'       => 'publish',
    'showMeta'     => true,
    'layout'       => 'card',
    'columns'      => 3,
]);

$type        = sanitize_key($attributes['type']);
$perPage     = max(1, (int) $attributes['itemsPerPage']);
$currentPage = max(1, (int) $attributes['currentPage']);
$sort        = sanitize_key($attributes['sort']);
$status      = sanitize_key($attributes['status']);
$showMeta    = (bool) $attributes['showMeta'];
$layout      = sanitize_key($attributes['layout']);
$columns     = (int) $attributes['columns'];

/*
|--------------------------------------------------------------------------
| Taxonomy Context
|--------------------------------------------------------------------------
*/

$object = get_queried_object();

if ($object instanceof WP_Term) {
    $taxonomy    = $object->taxonomy;
    $taxonomyObj = get_taxonomy($taxonomy);

    if ($taxonomyObj && !empty($taxonomyObj->object_type)) {
        $type = $taxonomyObj->object_type[0];
    }

    $cleanTax              = str_replace('_tax', '', $taxonomy);
    $attributes[$cleanTax] = [$object->slug];
}

/*
|--------------------------------------------------------------------------
| Validate PostType
|--------------------------------------------------------------------------
*/

if (!ApiConfig::isAllowedPostType($type)) {
    $type = ApiConfig::allowedPostTypes()[0] ?? 'paper';
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$activeFilters  = ApiConfig::extractAllowedFilters($type, $attributes);
$allFilters     = ApiConfig::allowedFilters($type);
$orderedButtons = [];
$queryFilters   = [];

foreach ($allFilters as $key) {
    $orderedButtons[$key] = [];

    if (!isset($activeFilters[$key])) {
        continue;
    }

    $values = array_filter(
        (array) $activeFilters[$key],
        static fn($v) => $v !== null && $v !== '' && $v !== '-' && $v !== '–'
    );

    if (!$values) {
        continue;
    }

    // Map camelCase attribute keys → snake_case API keys.
    $apiKey                = $attributeToApiMap[$key] ?? $key;
    $queryFilters[$apiKey] = array_values($values);
}

$orderedButtons['sort'] = [];

/*
|--------------------------------------------------------------------------
| Repository
|--------------------------------------------------------------------------
*/

$factory    = new RepositoryFactory($GLOBALS['wpdb']);
$repository = $factory->createRepositoryForPostType($type);

if (!$repository) {
    echo '<p>' . esc_html__('Invalid post type.', 'interactivity-docs') . '</p>';
    return;
}

/*
|--------------------------------------------------------------------------
| Query Data
|--------------------------------------------------------------------------
*/

$data = $repository->findPaginated([
    'page'     => $currentPage,
    'per_page' => $perPage,
    'filters'  => $queryFilters,
    'status'   => $status,
    'sort_by'  => $sort,
    'sort_dir' => 'DESC',
]);

$items = $data['items'] ?? [];

/*
|--------------------------------------------------------------------------
| Count & Pagination
|--------------------------------------------------------------------------
*/

$count      = $repository->countByFilters($queryFilters, $status);
$totalPages = (int) ceil($count / $perPage);
$pagination = UIHelpers::paginate(['current' => $currentPage, 'max' => $totalPages]);

/*
|--------------------------------------------------------------------------
| Faceted Meta
|--------------------------------------------------------------------------
*/

$meta = $showMeta
    ? $repository->getFacetedMeta($queryFilters, $status)
    : [];

/*
|--------------------------------------------------------------------------
| Interactivity Context
|--------------------------------------------------------------------------
*/

$results = array_map(static fn($item) => ['data' => $item], $items);

$context = [
    'mode'     => 'list',
    'endpoint' => 'interactivity-docs/v1/docs',

    'data' => [
        'items'       => $results,
        'meta'        => $meta,
        'currentMeta' => UIHelpers::buildCurrentMeta($results),
        'count'       => $count,
        'pagination'  => $pagination,
        'pagesCount'  => $totalPages,
    ],

    'query' => [
        'type'    => $type,
        'page'    => $currentPage,
        'perPage' => $perPage,
        'sort'    => $sort,
        'filters' => (object) $queryFilters,
    ],

    'selectedFilters' => [
        'map'   => [],
        'order' => [],
    ],

    'ui' => [
        'isLoading'     => false,
        'menuOpenedBy'  => $orderedButtons,
        'previousFocus' => $orderedButtons,
        'dropdownMenu'  => $orderedButtons,
    ],

    'cache' => new stdClass(),

    'singlePage' => [
        'trigger'      => ['tax' => null, 'value' => null],
        'triggerIndex' => null,
        'snapshot'     => [],
    ],
];

?>
<div
    data-wp-interactive="interactivity-docs"
    data-wp-bind--hidden="state.isHide"
    <?php echo wp_interactivity_data_wp_context($context); ?>
    class="container"
    data-wp-watch="callbacks.changeState"
    data-wp-init="callbacks.loadState"
>
<?php
    echo TemplateLoader::render($blockPath, 'layout/filters', ['type' => $attributes['type'], 'title' => $type]);
    echo TemplateLoader::render($blockPath, 'layout/docs-list', ['type' => $attributes['type'], 'layout' => $layout, 'columns' => $columns]);
    echo TemplateLoader::render($blockPath, 'layout/pagination');
?>
</div>
