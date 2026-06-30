<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Models\BaseEntity;

defined('ABSPATH') || exit;

/**
 * Repository Interface
 *
 * Defines the standard CRUD operations for all repositories.
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
interface RepositoryInterface
{
    /**
     * Find an entity by ID
     * @param int $id Entity ID
     * @return BaseEntity|null Entity instance or null if not found
     */
    public function find(int $id): ?BaseEntity;

    /**
     * Insert a new entity
     *
     * @param BaseEntity $entity Entity to insert
     * @return bool True on success, false on failure
     */
    public function insert(BaseEntity $entity): bool;

    /**
     * Update an existing entity
     *
     * @param BaseEntity $entity Entity to update
     * @return bool True on success, false on failure
     */
    public function update(BaseEntity $entity): bool;

    /**
     * Delete an entity by ID
     *
     * @param int $id Entity ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool;

    /**
     * Find all entities matching criteria
     *
     * @param array $criteria Optional filter criteria (WHERE conditions, ORDER BY, LIMIT, etc.)
     * @return array Array of entity instances
     */
    public function findAll(array $criteria = []): array;
}
