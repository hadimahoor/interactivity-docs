<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

defined('ABSPATH') || exit;

/**
 * Relation Repository
 *
 * Generic repository for managing many-to-many relationships between entities.
 * Can be used for paper-person, book-person, or any other junction table.
 *  
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
final class RelationRepository implements RelationRepositoryInterface
{
    private \wpdb $db;
    private string $table;
    private string $parentColumn;
    private string $relatedColumn;

    /**
     * Constructor
     *
     * @param \wpdb  $db            WordPress database instance
     * @param string $table         Junction table name (e.g., 'paper_person')
     * @param string $parentColumn  Parent entity column name (e.g., 'paper_id')
     * @param string $relatedColumn Related entity column name (e.g., 'person_id')
     */
    public function __construct(\wpdb $db, string $table, string $parentColumn, string $relatedColumn)
    {
        $this->db            = $db;
        $this->table         = $table;
        $this->parentColumn  = $parentColumn;
        $this->relatedColumn = $relatedColumn;
    }

    /**
     * Insert a relationship between a parent and related entity
     *
     * @param int $parentId  ID of the parent entity
     * @param int $relatedId ID of the related entity
     * @return bool True on success, false on failure
     */
    public function insert(int $parentId, int $relatedId): bool
    {
        return (bool) $this->db->insert(
            $this->table,
            [
                $this->parentColumn  => $parentId,
                $this->relatedColumn => $relatedId,
            ],
            ['%d', '%d']
        );
    }

    /**
     * Remove a specific relationship between a parent and related entity
     *
     * @param int $parentId  ID of the parent entity
     * @param int $relatedId ID of the related entity
     * @return bool True on success, false on failure
     */
    public function remove(int $parentId, int $relatedId): bool
    {
        return (bool) $this->db->delete(
            $this->table,
            [
                $this->parentColumn  => $parentId,
                $this->relatedColumn => $relatedId,
            ],
            ['%d', '%d']
        );
    }

    /**
     * Delete all relationships for a parent entity
     *
     * @param int $parentId ID of the parent entity
     * @return bool True on success, false on failure
     */
    public function delete(int $parentId): bool
    {
        return (bool) $this->db->delete(
            $this->table,
            [$this->parentColumn => $parentId],
            ['%d']
        );
    }

    /**
     * Get all related entity IDs for a parent entity
     *
     * @param int $parentId ID of the parent entity
     * @return int[] Array of related entity IDs
     */
    public function getRelated(int $parentId): array
    {
        $results = $this->db->get_col(
            $this->db->prepare(
                "SELECT {$this->relatedColumn} FROM {$this->table} WHERE {$this->parentColumn} = %d",
                $parentId
            )
        );

        return array_map('intval', $results);
    }

    /**
     * Get all parent entity IDs related to a specific entity
     *
     * @param int $relatedId ID of the related entity
     * @return int[] Array of parent entity IDs
     */
    public function getParentIds(int $relatedId): array
    {
        $results = $this->db->get_col(
            $this->db->prepare(
                "SELECT {$this->parentColumn} FROM {$this->table} WHERE {$this->relatedColumn} = %d",
                $relatedId
            )
        );

        return array_map('intval', $results);
    }

    /**
     * Get all person IDs associated with a post (paper or book)
     *
     * This is an alias for getRelated() when dealing with person relationships.
     *
     * @param int $postId ID of the post entity
     * @return int[] Array of person IDs
     */
    public function getPersonIdsForObject(int $postId): array
    {
        return $this->getRelated($postId);
    }



    /**
     * Begin a database transaction.
     *
     * Note: Only effective on transactional storage engines (e.g., InnoDB).
     * On MyISAM tables this is a no-op and rollback will not work.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->db->query('START TRANSACTION');
    }

    /**
     * Commit the current database transaction.
     * @return void
     */
    public function commit(): void
    {
        $this->db->query('COMMIT');
    }

    /**
     * Roll back the current database transaction.
     *
     * @return void
     */
    public function rollback(): void
    {
        $this->db->query('ROLLBACK');
    }
}
