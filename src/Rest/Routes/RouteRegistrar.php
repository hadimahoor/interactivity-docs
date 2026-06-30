<?php

declare(strict_types=1);

namespace InteractivityDocs\Rest\Routes;

use InteractivityDocs\Repository\RepositoryFactory;
use InteractivityDocs\Rest\Controllers\DocsController;

defined('ABSPATH') || exit;

/**
 * Registers REST API routes for the Interactivity Docs plugin.
 *
 * Coordinates the initialization of repositories, controllers, and route definitions
 * when WordPress REST API is ready.
 */
final class RouteRegistrar
{
    /**
     * @param \wpdb $db WordPress database access abstraction.
     */
    public function __construct(
        private readonly \wpdb $db
    ) {
    }

    /**
     * Registers WordPress hooks for REST API initialization.
     */
    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Instantiates and wires up the REST API routes.
     *
     * Creates the dependency chain: RepositoryFactory → DocsController → DocsRoutes,
     * then registers all routes with WordPress.
     */
    public function registerRoutes(): void
    {
        $repositoryFactory = new RepositoryFactory($this->db);
        $docsController    = new DocsController($repositoryFactory);
        $docsRoutes        = new DocsRoutes($docsController);

        $docsRoutes->register();
    }
}
