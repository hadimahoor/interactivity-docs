<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Models\BaseEntity;

defined('ABSPATH') || exit;

/**
 * Post Repository Interface
 *
 * Extends the base repository interface with post-specific operations,
 * including synchronization, faceted search, and filtering capabilities.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * Get the post type associated with this repository
     *
     * @return string Post type slug
     */
    public function getPostType(): string;

    /**
     * Fetch entity data for synchronization operations
     *
     * Returns raw entity data for system-level processing and sync operations.
     *
     * @param int $postId WordPress post ID
     * @return BaseEntity|null Entity instance or null if not found
     */
    public function fetchForSync(int $postId): ?BaseEntity;

    /**
     * Insert or update entity in main table
     *
     * Performs an upsert operation (insert if new, update if exists).
     *
     * @param BaseEntity $entity Entity to insert or update
     * @return bool True on success, false on failure
     */
    public function upsertMain(BaseEntity $entity): bool;

    /**
     * Delete entity by WordPress post ID
     *
     * @param int $postId WordPress post ID
     * @return bool True on success, false on failure
     */
    public function deleteByPostId(int $postId): bool;

    /**
     * Check if entity exists by post ID
     *
     * @param int $postId WordPress post ID
     * @return bool True if exists, false otherwise
     */
    public function exists(int $postId): bool;

    /**
     * Find entities with pagination
     *
     * @param array $args Query arguments including page, per_page, filters, orderby, order
     * @return array Array with 'items' and 'total' keys
     */
    public function findPaginated(array $args): array;

    /**
     * Get faceted metadata for filtered results
     *
     * Returns available filter options (facets) based on current filters,
     * useful for building dynamic filter UI.
     *
     * @param array $filters Active filters to apply
     * @param string|null $status Post status filter (default: 'publish')
     * @param array|null $only Optional list of specific facets to retrieve
     * @return array Faceted metadata by filter key
     */
    public function getFacetedMeta(array $filters, ?string $status = 'publish', ?array $only = null): array;

    /**
     * Count entities matching filters
     *
     * @param array $filters Filter criteria
     * @param string|null $status Post status filter (default: 'publish')
     * @return int Total count
     */
    public function countByFilters(array $filters, ?string $status = 'publish'): int;

    /**
     * Get all available facet options
     *
     * Returns all possible values for each facet/filter across the entire dataset.
     *
     * @param string|null $status Post status filter (default: 'publish')
     * @param int $limitPerFacet Maximum options per facet (default: 200)
     * @return array Facet options grouped by filter key
     */
    public function getAllFacetOptions(?string $status = 'publish', int $limitPerFacet = 200): array;

    /**
     * Get unique values for a specific column
     *
     * @param string $column Column name to retrieve unique values from
     * @param string|null $status Post status filter (default: 'publish')
     * @param int $limit Maximum number of unique values (default: 200)
     * @return array Array of unique values
     */
    public function getUniqueValues(string $column, ?string $status = 'publish', int $limit = 200): array;
}
