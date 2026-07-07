<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

defined('ABSPATH') || exit;

/**
 * Relation Repository Interface
 *
 * Defines the contract for managing many-to-many relationships between entities,
 * such as papers/books and their authors (persons).
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
interface RelationRepositoryInterface
{
    /**
     * Insert a relationship between a parent and related entity
     *
     * @param int $parentId ID of the parent entity (e.g., paper_id or book_id)
     * @param int $relatedId ID of the related entity (e.g., person_id)
     * @return bool True on success, false on failure
     */
    public function insert(int $parentId, int $relatedId): bool;

    /**
     * Remove a specific relationship between a parent and related entity
     *
     * @param int $parentId ID of the parent entity
     * @param int $relatedId ID of the related entity
     * @return bool True on success, false on failure
     */
    public function remove(int $parentId, int $relatedId): bool;

    /**
     * Delete all relationships for a parent entity
     *
     * @param int $parentId ID of the parent entity
     * @return bool True on success, false on failure
     */
    public function delete(int $parentId): bool;

    /**
     * Get all related entity IDs for a parent entity
     *
     * @param int $parentId ID of the parent entity
     * @return int[] Array of related entity IDs
     */
    public function getRelated(int $parentId): array;

    /**
     * Get all parent entity IDs related to a specific entity
     *
     * @param int $relatedId ID of the related entity
     * @return int[] Array of parent entity IDs
     */
    public function getParentIds(int $relatedId): array;

    /**
     * Get all person IDs associated with a post (paper or book)
     *
     * @param int $postId ID of the post entity
     * @return int[] Array of person IDs
     */
    public function getPersonIdsForObject(int $postId): array;

        /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit the current database transaction.
     */
    public function commit(): void;

    /**
     * Roll back the current database transaction.
     */
    public function rollback(): void;
}
