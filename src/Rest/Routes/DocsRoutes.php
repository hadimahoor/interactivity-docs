<?php

declare(strict_types=1);

namespace InteractivityDocs\Rest\Routes;

use WP_REST_Server;
use WP_REST_Request;
use InteractivityDocs\Rest\Controllers\DocsController;
use InteractivityDocs\Rest\Config\ApiConfig;

defined('ABSPATH') || exit;

/**
 * Registers REST API routes for documents.
 *
 * Handles two endpoints:
 * - /docs: Main documents query with filters
 * - /docs/facets: Available filter options and counts
 */
final class DocsRoutes
{
    private const DOCS_ENDPOINT = '/docs';
    private const FACETS_ENDPOINT = '/docs/facets';

    public function __construct(
        private readonly DocsController $docsController
    ) {
    }

    /**
     * Registers all document-related endpoints.
     */
    public function register(): void
    {
        $this->registerDocsEndpoint();
        $this->registerFacetsEndpoint();
    }

    /**
     * Registers the main documents query endpoint.
     *
     * GET /wp-json/interactivity-docs/v1/docs
     */
    private function registerDocsEndpoint(): void
    {
        register_rest_route(ApiConfig::REST_NAMESPACE, self::DOCS_ENDPOINT, [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this->docsController, 'handle'],
            'permission_callback' => fn() => $this->checkPermission(),
            'args'                => ApiConfig::docsArgs(),
        ]);
    }

    /**
     * Registers the facets endpoint for filter metadata.
     *
     * GET /wp-json/interactivity-docs/v1/docs/facets
     */
    private function registerFacetsEndpoint(): void
    {
        register_rest_route(ApiConfig::REST_NAMESPACE, self::FACETS_ENDPOINT, [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this->docsController, 'getFacets'],
            'permission_callback' => fn() => $this->checkPermission(),
            'args'                => ApiConfig::facetsArgs(),
        ]);
    }

    /**
     * Checks if the current user can access the endpoint.
     *
     * Currently allows public access. The $request parameter is reserved
     * for future authentication/authorization logic if needed.
     *
     * @param WP_REST_Request|null $request The REST request object (unused)
     * @return bool True if access is allowed
     */
    private function checkPermission(?WP_REST_Request $request = null): bool
    {
        // Public endpoints - add authentication logic here if needed
        return true;
    }
}
