<?php

declare(strict_types=1);

namespace InteractivityDocs\Sync;

use InteractivityDocs\Sync\PostSyncManager;
use InteractivityDocs\Sync\RelationSyncService;
use InteractivityDocs\Repository\RepositoryFactoryInterface;
use Psr\Log\LoggerInterface;

defined('ABSPATH') || exit;

/**
 * Coordinates synchronization operations across post types and relationships.
 *
 * This coordinator acts as the central orchestrator for all sync operations,
 * handling WordPress hooks and ensuring proper sequencing of atomic syncs,
 * relationship syncs, and cascading updates.
 *
 * Sync strategy:
 * - Atomic sync: Updates the post record and JSON fields only
 * - Relation sync: Updates many-to-many relationship tables
 * - Cascading updates: When a person changes, updates all related content
 */
class SyncCoordinator
{
    private PostSyncManager $postSyncManager;
    private RelationSyncService $relationSyncService;
    private RepositoryFactoryInterface $repositoryFactory;
    private ?LoggerInterface $logger;

    /**
     * Post types supported by the sync system.
     */
    private const SUPPORTED_POST_TYPES = ['paper', 'book', 'person'];

    /**
     * Post types that have person relationships.
     */
    private const RELATIONAL_POST_TYPES = ['paper', 'book'];

    /**
     * ACF field name for author relationships.
     * Override this if using a different field name.
     */
    private const AUTHOR_FIELD = 'author';

    /**
     * Mapping of relation types to post types for cascade updates.
     */
    private const RELATION_TYPE_MAP = [
        'paper_person' => 'paper',
        'book_person' => 'book'
    ];

    public function __construct(
        PostSyncManager $postSyncManager,
        RelationSyncService $relationSyncService,
        RepositoryFactoryInterface $repositoryFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->postSyncManager = $postSyncManager;
        $this->relationSyncService = $relationSyncService;
        $this->repositoryFactory = $repositoryFactory;
        $this->logger = $logger;
    }

    /**
     * Registers WordPress hooks for sync operations.
     *
     * Priority 20 on save_post ensures ACF fields are already saved.
     */
    public function init(): void
    {
        add_action('save_post', [$this, 'coordinateSave'], 20, 2);
        add_action('before_delete_post', [$this, 'coordinateDelete'], 10, 1);
        $this->logInfo('SyncCoordinator initialized');
    }

    /**
     * Coordinates full sync when a post is saved.
     *
     * Sync flow:
     * 1. Atomic sync: Update the post record itself
     * 2. Relation sync: Update relationship tables (for papers/books)
     * 3. Cascade updates: If person changed, update all related content
     *
     * @param int $postId Post ID being saved
     * @param \WP_Post $post Post object
     */
    public function coordinateSave(int $postId, \WP_Post $post): void
    {
        if ($this->shouldSkipSync($postId, $post)) {
            return;
        }

        try {
            // Step 1: Atomic sync (post record + JSON fields)
            $this->doAtomicSync($postId, $post);

            // Step 2: Relation sync (for papers and books)
            if ($this->isRelationalPostType($post->post_type)) {
                $this->doRelationSync($postId, $post->post_type);
            }

            // Step 3: Cascade updates (when a person changes)
            if ($post->post_type === 'person') {
                $this->updateRelatedContents($postId);
            }

            $this->logInfo("Successfully coordinated save for {$post->post_type} {$postId}");
        } catch (\Exception $e) {
            $this->logError("Failed to coordinate save for {$post->post_type} {$postId}: {$e->getMessage()}", [
                'post_id' => $postId,
                'post_type' => $post->post_type,
                'exception' => $e
            ]);
            // Don't rethrow - allow WordPress save to complete
        }
    }

    /**
     * Coordinates cleanup when a post is deleted.
     *
     * @param int $postId Post ID being deleted
     */
    public function coordinateDelete(int $postId): void
    {
        $post = get_post($postId);
        if (!$post || !$this->isSupportedPostType($post->post_type)) {
            return;
        }

        try {
            // Step 1: Delete from main tables
            $this->postSyncManager->deletePost($post);

            // Step 2: Clean up relationships (sync with empty array)
            if ($this->isRelationalPostType($post->post_type)) {
                $this->relationSyncService->syncPeople($postId, [], $post->post_type);
            }

            $this->logInfo("Successfully coordinated delete for {$post->post_type} {$postId}");
        } catch (\Exception $e) {
            $this->logError("Failed to coordinate delete for {$post->post_type} {$postId}: {$e->getMessage()}", [
                'post_id' => $postId,
                'post_type' => $post->post_type,
                'exception' => $e
            ]);
            // Don't rethrow - allow WordPress deletion to complete
        }
    }

    /**
     * Performs atomic sync: updates only the post record and JSON fields.
     * Does not touch relationship tables.
     *
     * @param int $postId Post ID
     * @param \WP_Post $post Post object
     */
    private function doAtomicSync(int $postId, \WP_Post $post): void
    {
        $this->postSyncManager->syncPost($postId, $post);
    }

    /**
     * Performs relation sync: updates many-to-many relationship tables.
     * Does not touch the post record itself.
     *
     * @param int $postId Post ID
     * @param string $postType Post type
     */
    private function doRelationSync(int $postId, string $postType): void
    {
        $personIds = get_field(self::AUTHOR_FIELD, $postId, false);
        $this->relationSyncService->syncPeople($postId, $personIds, $postType);
    }

    /**
     * Cascading update: when a person changes, update all related content.
     *
     * This ensures that if a person's data changes (name, bio, etc.),
     * all papers and books by that person are re-synced to reflect the new data
     * in their JSON fields.
     *
     * Only performs atomic sync on related content - does not modify relationships.
     *
     * @param int $personId Person post ID
     */
    private function updateRelatedContents(int $personId): void
    {
        foreach (self::RELATION_TYPE_MAP as $relationType => $postType) {
            try {
                $repo = $this->repositoryFactory->createRelationRepository($relationType);
                $parentIds = $repo->getParentIds($personId);
                if (empty($parentIds)) {
                    continue;
                }

                foreach ($parentIds as $id) {
                    $post = get_post((int)$id);

                    // Validate post exists and matches expected type
                    if (!$post || $post->post_type !== $postType) {
                        $this->logWarning("Skipping invalid post {$id} for person {$personId}");
                        continue;
                    }

                    // Only atomic sync - don't touch relationships
                    $this->doAtomicSync((int)$id, $post);
                }

                $this->logInfo("Updated {$postType} content for person {$personId}", [
                    'person_id' => $personId,
                    'post_type' => $postType,
                    'count' => count($parentIds)
                ]);
            } catch (\Exception $e) {
                $this->logError("Failed to update {$postType} content for person {$personId}: {$e->getMessage()}", [
                    'person_id' => $personId,
                    'post_type' => $postType,
                    'exception' => $e
                ]);
                // Continue with other post types
            }
        }
    }

    /**
     * Determines if sync should be skipped for safety reasons.
     *
     * @param int $postId Post ID
     * @param \WP_Post $post Post object
     * @return bool True if sync should be skipped
     */
    private function shouldSkipSync(int $postId, \WP_Post $post): bool
    {
        return (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
               $post->post_status === 'auto-draft' ||
               wp_is_post_revision($postId) !== false ||
               !$this->isSupportedPostType($post->post_type);
    }

    /**
     * Checks if a post type is supported by the sync system.
     *
     * @param string $postType Post type to check
     * @return bool True if supported
     */
    private function isSupportedPostType(string $postType): bool
    {
        return in_array($postType, self::SUPPORTED_POST_TYPES, true);
    }

    /**
     * Checks if a post type has person relationships.
     *
     * @param string $postType Post type to check
     * @return bool True if post type can have person relationships
     */
    private function isRelationalPostType(string $postType): bool
    {
        return in_array($postType, self::RELATIONAL_POST_TYPES, true);
    }

    /**
     * Logs an info message if a logger is configured.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * Logs a warning message if a logger is configured.
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * Logs an error message if a logger is configured.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }
}
