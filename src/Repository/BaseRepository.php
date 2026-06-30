<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

defined('ABSPATH') || exit;

/**
 * Base Repository
 *
 * Abstract base class providing common repository functionality for all entities.
 * Handles database connection, table reference, and basic CRUD operations.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * WordPress database object
     *
     * @var \wpdb
     */
    protected \wpdb $db;

    /**
     * Full table name (including prefix)
     *
     * @var string
     */
    protected string $table;

    /**
     * Primary key column name
     *
     * @var string
     */
    protected string $idColumn;

    /**
     * Constructor
     *
     * @param \wpdb  $db       WordPress database object
     * @param string $table    Full table name
     * @param string $idColumn Primary key column name
     */
    public function __construct(\wpdb $db, string $table, string $idColumn)
    {
        $this->db       = $db;
        $this->table    = $table;
        $this->idColumn = $idColumn;
    }

    /**
     * Delete an entity by ID
     *
     * @param int $id Entity ID
     * @return bool True on success, false on failure
     */
    public function delete(int $id): bool
    {
        return (bool) $this->db->delete($this->table, [$this->idColumn => $id], ['%d']);
    }

    // Methods that vary by repository are implemented in child classes
}
