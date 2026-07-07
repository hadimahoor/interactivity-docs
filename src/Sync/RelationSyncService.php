<?php

declare(strict_types=1);

namespace InteractivityDocs\Sync;

use InteractivityDocs\Repository\RepositoryFactoryInterface;
use InteractivityDocs\Repository\PersonRepository;
use InteractivityDocs\Repository\RelationRepositoryInterface;
use Psr\Log\LoggerInterface;

defined('ABSPATH') || exit;

/**
 * Manages synchronization of many-to-many relationships between posts and people.
 *
 * This service handles the syncing of relationships (e.g., paper-author, book-author)
 * and employs a self-healing strategy to recalculate counters only for affected
 * entities, ensuring database consistency without unnecessary overhead.
 */
class RelationSyncService
{
    private RepositoryFactoryInterface $repositoryFactory;
    private ?LoggerInterface $logger;

    /**
     * Valid post types that can have person relationships.
     */
    private const VALID_POST_TYPES = ['paper', 'book'];

    public function __construct(RepositoryFactoryInterface $repositoryFactory, ?LoggerInterface $logger = null)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->logger = $logger;
    }

    /**
     * Synchronizes person relationships for a given post.
     *
     * This method:
     * 1. Identifies what changed (delta detection)
     * 2. Updates the relationship table
     * 3. Recalculates counters only for affected people (self-healing)
     *
     * The self-healing approach ensures that if counters drift due to manual
     * database changes or bugs, they are automatically corrected during sync.
     *
     * @param int $postId The post ID (paper or book)
     * @param mixed $newPersonIds Person IDs to associate (can be array, single ID, or null)
     * @param string $postType The post type ('paper' or 'book')
     * @throws \InvalidArgumentException If post type is not valid
     */
    public function syncPeople(int $postId, mixed $newPersonIds, string $postType): void
    {
        // Validate post type
        if (!in_array($postType, self::VALID_POST_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid post type: {$postType}. Expected: " . implode(', ', self::VALID_POST_TYPES));
        }

        try {
            // Get required repositories
            $relationRepo = $this->getRelationRepository($postType);
            $personRepo = $this->getPersonRepository();

            // Detect changes (delta)
            $oldPersonIds = $relationRepo->getPersonIdsForObject($postId);  
            $newPersonIds = $this->normalizeIds($newPersonIds);

            // Calculate diff for logging and optimization
            $added = array_diff($newPersonIds, $oldPersonIds);
            $removed = array_diff($oldPersonIds, $newPersonIds);

            // Skip if nothing changed
            if (empty($added) && empty($removed)) {
                return;
            }

            // Perform relationship sync
            $this->updateRelations($relationRepo, $postId, $oldPersonIds, $newPersonIds);

            // Self-healing: recalculate counters only for affected people
            $affectedIds = array_unique(array_merge($added, $removed));
            if (!empty($affectedIds)) {
                $personRepo->recalculateCounts($affectedIds, $postType);
            }

            // Log changes
            $this->logInfo("Synced {$postType} relationships for post {$postId}", [
                'post_id' => $postId,
                'post_type' => $postType,
                'added' => count($added),
                'removed' => count($removed),
                'affected_people' => $affectedIds
            ]);
        } catch (\Exception $e) {
            $this->logError("Failed to sync {$postType} relationships for post {$postId}: {$e->getMessage()}", [
                'post_id' => $postId,
                'post_type' => $postType,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Updates relationship records in the database atomically.
     *
     * Uses a delete-and-reinsert strategy wrapped in a transaction so the
     * record never ends up in a partial state if an insert fails midway.
     * Because insert()/delete() return booleans (not exceptions), each result
     * is checked explicitly and a failure throws to trigger the rollback.
     *
     * @param RelationRepository $relationRepo Relation repository instance
     * @param int                $postId       Parent post ID
     * @param int[]              $oldPersonIds Current related person IDs (kept for the optimized strategy)
     * @param int[]              $newPersonIds Desired related person IDs
     * @return void
     * @throws \Throwable Re-thrown after rollback so the caller can react.
     */
    private function updateRelations(RelationRepositoryInterface $relationRepo, int $postId, array $oldPersonIds, array $newPersonIds): void
    {
        $relationRepo->beginTransaction();

        try {
        // Only attempt to delete if there were existing relations
            if (!empty($oldPersonIds)) {
                $deleteResult = $relationRepo->delete($postId);

                if ($deleteResult === false) {
                    throw new \RuntimeException("Failed to delete relations for post {$postId}");
                }
            }

        // Insert new relations

            foreach ($newPersonIds as $personId) {
                if ($relationRepo->insert($postId, (int) $personId) === false) {
                    throw new \RuntimeException("Failed to insert relation {$postId} -> {$personId}");
                }
            }

            $relationRepo->commit();
        } catch (\Throwable $e) {
            $relationRepo->rollback();
            throw $e;
        }
                // Alternative optimized approach (commented out):
        // $toRemove = array_diff($oldPersonIds, $newPersonIds);
        // $toAdd = array_diff($newPersonIds, $oldPersonIds);
        // foreach ($toRemove as $personId) {
        //     $relationRepo->deleteSpecific($postId, $personId);
        // }
        // foreach ($toAdd as $personId) {
        //     $relationRepo->insert($postId, $personId);
        // }
    }

    /**
     * Normalizes and validates person IDs.
     *
     * Handles various input formats:
     * - null or empty → returns empty array
     * - Single ID → returns array with one element
     * - Array of IDs → filters and deduplicates
     *
     * Filters out invalid values (non-numeric, zero, negative).
     *
     * @param mixed $ids Raw person IDs from ACF or other source
     * @return int[] Array of validated, unique person IDs
     */
    private function normalizeIds(mixed $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $idsArray = is_array($ids) ? $ids : [$ids];

        return array_values(array_unique(array_map(
            'intval',
            array_filter($idsArray, fn($id) => is_numeric($id) && (int)$id > 0)
        )));
    }

    /**
     * Gets the appropriate relation repository based on post type.
     *
     * @param string $postType
     * @return RelationRepository
     */
    private function getRelationRepository(string $postType): RelationRepositoryInterface
    {
        $relationName = $postType . '_person';
        try {
            return $this->repositoryFactory->createRelationRepository($relationName);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to create relation repository for: {$relationName}",
                0,
                $e
            );
        }
    }


    /**
     * Gets the person repository.
     *
     * @return PersonRepository
     */
    private function getPersonRepository(): PersonRepository
    {
        $repo = $this->repositoryFactory->createRepositoryForPostType('person');
        if (!$repo instanceof PersonRepository) {
            throw new \RuntimeException("Failed to create PersonRepository");
        }

        return $repo;
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
     * Logs an error message if a logger is configured.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }
}
