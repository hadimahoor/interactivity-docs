<?php

declare(strict_types=1);

namespace InteractivityDocs\Cli\Commands;

use InteractivityDocs\Sync\SyncCoordinator;
use InteractivityDocs\Sync\PostSyncManager;
use InteractivityDocs\Sync\RelationSyncService;
use InteractivityDocs\Repository\RepositoryFactory;
use WP_CLI;

defined('ABSPATH') || exit;

/**
 * Sync posts to custom tables.
 *
 * ## EXAMPLES
 *
 *     # Sync all supported post types
 *     $ wp docs sync --all
 *
 *     # Sync only books
 *     $ wp docs sync --post-type=book
 *
 *     # Sync with custom batch size
 *     $ wp docs sync --post-type=paper --batch-size=50
 *
 *     # Dry run (show what would be synced without actually syncing)
 *     $ wp docs sync --all --dry-run
 *
 *     # Sync specific posts by ID
 *     $ wp docs sync --post-ids=123,456,789
 */
class SyncCommand
{
    // Dependency order: person must sync first (referenced by paper/book)
    private const SUPPORTED_POST_TYPES = ['person', 'paper', 'book'];
    private const DEFAULT_BATCH_SIZE = 100;

    private SyncCoordinator $coordinator;

    /**
     * Sync posts to custom tables.
     *
     * ## OPTIONS
     *
     * [--post-type=<type>]
     * : Post type to sync. Must be one of: paper, book, person
     *
     * [--all]
     * : Sync all supported post types
     *
     * [--post-ids=<ids>]
     * : Comma-separated list of specific post IDs to sync
     *
     * [--batch-size=<size>]
     * : Number of posts to process in each batch
     * ---
     * default: 100
     * ---
     *
     * [--dry-run]
     * : Show what would be synced without actually syncing
     *
     * [--force]
     * : Force sync even for posts that appear unchanged
     *
     * @when after_wp_load
     */
    public function __invoke($args, $assoc_args)
    {
        $this->initCoordinator();

        // Parse arguments
        $postType = $assoc_args['post-type'] ?? null;
        $syncAll = isset($assoc_args['all']);
        $postIds = isset($assoc_args['post-ids']) ? $this->parsePostIds($assoc_args['post-ids']) : null;
        $batchSize = (int)($assoc_args['batch-size'] ?? self::DEFAULT_BATCH_SIZE);
        $dryRun = isset($assoc_args['dry-run']);
        $force = isset($assoc_args['force']);

        // Validate arguments
        if (!$syncAll && !$postType && !$postIds) {
            WP_CLI::error('You must specify --all, --post-type, or --post-ids');
            return;
        }

        if ($postType && !in_array($postType, self::SUPPORTED_POST_TYPES, true)) {
            WP_CLI::error('Invalid post type. Must be one of: ' . implode(', ', self::SUPPORTED_POST_TYPES));
            return;
        }

        if ($batchSize < 1) {
            WP_CLI::error('Batch size must be at least 1');
            return;
        }

        // Determine which post types to sync
        $postTypes = $this->getPostTypes($syncAll, $postType);

        if ($dryRun) {
            WP_CLI::warning('DRY RUN MODE - No changes will be made');
        }

        // Sync by specific IDs if provided
        if ($postIds) {
            $this->syncByIds($postIds, $dryRun, $force);
            return;
        }

        // Sync by post type
        foreach ($postTypes as $type) {
            $this->syncPostType($type, $batchSize, $dryRun, $force);
        }

        WP_CLI::success('Sync completed!');
    }

    /**
     * Initialize the sync coordinator with proper dependencies.
     */
    private function initCoordinator(): void
    {
        global $wpdb;

        $repositoryFactory = new RepositoryFactory($wpdb);
        $postSyncManager = new PostSyncManager($repositoryFactory);
        $relationSyncService = new RelationSyncService($repositoryFactory);

        $this->coordinator = new SyncCoordinator(
            $postSyncManager,
            $relationSyncService,
            $repositoryFactory
        );
    }

    /**
     * Sync posts by specific IDs.
     */
    private function syncByIds(array $postIds, bool $dryRun, bool $force): void
    {
        $total = count($postIds);
        WP_CLI::log("Processing {$total} specific post(s)...");

        $progress = \WP_CLI\Utils\make_progress_bar('Syncing posts', $total);

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($postIds as $postId) {
            $post = get_post($postId);

            if (!$post) {
                WP_CLI::warning("Post {$postId} not found");
                $skipped++;
                $progress->tick();
                continue;
            }

            if (!in_array($post->post_type, self::SUPPORTED_POST_TYPES, true)) {
                WP_CLI::warning("Post {$postId} has unsupported type '{$post->post_type}'");
                $skipped++;
                $progress->tick();
                continue;
            }

            if ($dryRun) {
                WP_CLI::log("Would sync: {$post->post_type} #{$postId} - {$post->post_title}");
                $synced++;
            } else {
                try {
                    $this->coordinator->coordinateSave($postId, $post);
                    $synced++;
                } catch (\Exception $e) {
                    WP_CLI::warning("Failed to sync post {$postId}: {$e->getMessage()}");
                    $errors++;
                }
            }

            $progress->tick();
        }

        $progress->finish();

        $this->printSummary($synced, $skipped, $errors, $dryRun);
    }

    /**
     * Sync all posts of a specific post type.
     */
    private function syncPostType(string $postType, int $batchSize, bool $dryRun, bool $force): void
    {
        // Get total count
        $totalQuery = new \WP_Query([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
        ]);

        $total = $totalQuery->found_posts;

        if ($total === 0) {
            WP_CLI::log("No published {$postType} posts found. Skipping...");
            return;
        }

        WP_CLI::log("Found {$total} {$postType} post(s) to sync");

        $progress = \WP_CLI\Utils\make_progress_bar("Syncing {$postType}", $total);

        $synced = 0;
        $skipped = 0;
        $errors = 0;
        $offset = 0;

        while ($offset < $total) {
            $posts = get_posts([
                'post_type' => $postType,
                'post_status' => 'publish',
                'posts_per_page' => $batchSize,
                'offset' => $offset,
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);

            foreach ($posts as $post) {
                if ($dryRun) {
                    WP_CLI::log("Would sync: {$post->post_type} #{$post->ID} - {$post->post_title}");
                    $synced++;
                } else {
                    try {
                        $this->coordinator->coordinateSave($post->ID, $post);
                        $synced++;
                    } catch (\Exception $e) {
                        WP_CLI::warning("Failed to sync {$post->post_type} {$post->ID}: {$e->getMessage()}");
                        $errors++;
                    }
                }

                $progress->tick();
            }

            $offset += $batchSize;

            // Free memory
            wp_cache_flush();
        }

        $progress->finish();

        $this->printSummary($synced, $skipped, $errors, $dryRun);
    }

    /**
     * Get list of post types to sync.
     */
    private function getPostTypes(bool $syncAll, ?string $postType): array
    {
        if ($syncAll) {
            return self::SUPPORTED_POST_TYPES;
        }

        return $postType ? [$postType] : [];
    }

    /**
     * Parse comma-separated post IDs.
     */
    private function parsePostIds(string $ids): array
    {
        $parsed = array_map('intval', explode(',', $ids));
        return array_filter($parsed, fn($id) => $id > 0);
    }

    /**
     * Print sync summary.
     */
    private function printSummary(int $synced, int $skipped, int $errors, bool $dryRun): void
    {
        WP_CLI::log('');
        WP_CLI::log('Summary:');
        WP_CLI::log("  - Synced: {$synced}");

        if ($skipped > 0) {
            WP_CLI::log("  - Skipped: {$skipped}");
        }

        if ($errors > 0) {
            WP_CLI::log("  - Errors: {$errors}");
        }

        if ($dryRun) {
            WP_CLI::log('');
            WP_CLI::warning('This was a dry run. No changes were made.');
        }
    }
}
