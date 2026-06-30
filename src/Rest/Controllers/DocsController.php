<?php

declare(strict_types=1);

namespace InteractivityDocs\Rest\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use InteractivityDocs\Repository\PostRepositoryInterface;
use InteractivityDocs\Repository\RepositoryFactory;
use InteractivityDocs\Rest\Config\ApiConfig;
use InteractivityDocs\Rest\Config\SortConfig;

defined('ABSPATH') || exit;

/**
 * REST API controller for documents (papers, books, persons)
 *
 * Handles paginated listing with filters, sorting, and faceted metadata
 */
final class DocsController
{
    public function __construct(
        private readonly RepositoryFactory $repositoryFactory
    ) {
    }

    /**
     * Handle GET request for paginated documents with optional faceted metadata
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $postType = (string) $request->get_param('post_type');
        $repository = $this->repositoryFactory->createRepositoryForPostType($postType);

        if (!$repository instanceof PostRepositoryInterface) {
            return new WP_Error(
                'invalid_post_type',
                'Invalid post type.',
                ['status' => 400]
            );
        }

        $params = $request->get_params();

        // Extract only allowed filters for this post type
        // Automatically handles parameter aliasing (e.g., filter_year → year)
        $filters = ApiConfig::extractAllowedFilters($postType, $params);

        $status = (string) ($request->get_param('status') ?? ApiConfig::DEFAULT_STATUS);

        // Resolve sort key to column and direction
        $sortKey = (string) ($request->get_param('sort') ?? ApiConfig::DEFAULT_SORT);
        [$sortBy, $sortDir] = SortConfig::resolve($postType, $sortKey);

        $args = [
            'page'     => (int) ($request->get_param('page') ?? ApiConfig::DEFAULT_PAGE),
            'per_page' => (int) ($request->get_param('per_page') ?? ApiConfig::DEFAULT_PER_PAGE),
            'sort_by'  => $sortBy,
            'sort_dir' => $sortDir,
            'filters'  => $filters,
            'status'   => $status,
        ];

        try {
            // Fetch paginated results from repository
            $paginatedData = $repository->findPaginated($args);

            $includeMeta = rest_sanitize_boolean($request->get_param('include_meta') ?? true);
            $onlyItems   = rest_sanitize_boolean($request->get_param('only_items') ?? false);

            $meta = [];

            // Build faceted metadata if requested
            if ($includeMeta && !$onlyItems) {
                $onlyMeta = $request->get_param('only_meta');
                $only = null;

                if (is_array($onlyMeta)) {
                    $only = array_values(array_filter(array_map(
                        static fn($item): string => sanitize_key((string) $item),
                        $onlyMeta
                    )));
                }

                $meta = $repository->getFacetedMeta($filters, $status, $only);
            }

            $total = $repository->countByFilters($filters, $status);

            return new WP_REST_Response([
                'items' => $paginatedData['items'] ?? [],
                'pagination' => [
                    'total'    => $total,
                    'page'     => $paginatedData['page'] ?? $args['page'],
                    'per_page' => $paginatedData['per_page'] ?? $args['per_page'],
                    'pages'    => (int) ceil($total / max(1, $args['per_page'])),
                ],
                'meta' => $meta
            ], 200);
        } catch (\Throwable $e) {
            return new WP_Error(
                'internal_error',
                'An error occurred while processing the request.',
                ['status' => 500]
            );
        }
    }

    /**
     * Handle GET request for all facet options (for filter dropdowns)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getFacets(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $postType = sanitize_key((string) $request->get_param('post_type'));
        if ($postType === '') {
            return new WP_Error(
                'missing_post_type',
                'The "post_type" parameter is required.',
                ['status' => 400]
            );
        }

        $repository = $this->repositoryFactory->createRepositoryForPostType($postType);
        if (!$repository instanceof PostRepositoryInterface) {
            return new WP_Error(
                'invalid_post_type',
                sprintf('Unsupported post type: %s', $postType),
                ['status' => 400]
            );
        }

        $status = (string) ($request->get_param('status') ?? ApiConfig::DEFAULT_STATUS);
        $limit  = (int) ($request->get_param('limit') ?? 200);
        $limit  = max(1, min(500, $limit));

        return rest_ensure_response([
            'success' => true,
            'items'   => $repository->getAllFacetOptions($status, $limit),
        ]);
    }
}
