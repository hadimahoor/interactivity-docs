<?php

declare(strict_types=1);

namespace InteractivityDocs;

use InteractivityDocs\Database\SchemaManager;
use InteractivityDocs\PostTypes\PostTypeRegistrar;
use InteractivityDocs\Taxonomies\TaxonomyRegistrar;
use InteractivityDocs\BlockManager\BlockRegistrar;
use InteractivityDocs\Repository\RepositoryFactory;
use InteractivityDocs\Sync\PostSyncManager;
use InteractivityDocs\Sync\RelationSyncService;
use InteractivityDocs\Sync\SyncCoordinator;
use InteractivityDocs\Rest\Routes\RouteRegistrar;
use InteractivityDocs\Integration\AcfManager;

defined('ABSPATH') || exit;

/**
 * Main plugin orchestrator
 * Wires up all services with proper dependency injection
 */
final class Plugin
{
    private SchemaManager $schemaManager;
    private PostTypeRegistrar $postTypeRegistrar;
    private TaxonomyRegistrar $taxonomyRegistrar;
    private BlockRegistrar $blockRegistrar;
    private SyncCoordinator $syncCoordinator;
    private RouteRegistrar $routeRegistrar;
    private AcfManager $acfManager;
    public function __construct()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;
    // Build the dependency tree from the bottom up
        $repositoryFactory = new RepositoryFactory($wpdb);
        $postSyncManager = new PostSyncManager($repositoryFactory);
        $relationSyncService = new RelationSyncService($repositoryFactory);
        $this->syncCoordinator = new SyncCoordinator($postSyncManager, $relationSyncService, $repositoryFactory);
        $this->schemaManager = new SchemaManager();
        $this->postTypeRegistrar = new PostTypeRegistrar();
        $this->taxonomyRegistrar = new TaxonomyRegistrar();
        $this->blockRegistrar = new BlockRegistrar();
        $this->routeRegistrar = new RouteRegistrar($wpdb);
        $this->acfManager = new AcfManager();
    }

    /**
     * Runs once on plugin activation
     * Creates database schema and registers post types/taxonomies
     */
    public function activate(): void
    {
        $this->schemaManager->createTables();
        $this->postTypeRegistrar->register();
        $this->taxonomyRegistrar->register();
        flush_rewrite_rules();
    }

    /**
     * Boots the plugin on every request
     * Registers WordPress hooks and REST routes
     */
    public function boot(): void
    {
        add_action('init', [$this->postTypeRegistrar, 'register'], 5);
        add_action('init', [$this->taxonomyRegistrar, 'register'], 6);
        add_action('init', [$this->blockRegistrar, 'register'], 20);
        $this->routeRegistrar->registerHooks();
        $this->syncCoordinator->init();
        $this->acfManager->register();
    }
}
