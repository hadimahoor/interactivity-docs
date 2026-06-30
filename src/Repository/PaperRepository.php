<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Database\TableNames;
use InteractivityDocs\Models\BaseEntity;
use InteractivityDocs\Models\Paper;

defined('ABSPATH') || exit;

/**
 * Paper Repository
 *
 * Handles database operations for Paper entities, including upsert, filtering, and pagination.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
class PaperRepository extends BasePostRepository
{
    /**
     * Constructor
     *
     * @param \wpdb $db WordPress database object
     */
    public function __construct(\wpdb $db)
    {
        parent::__construct(
            $db,
            TableNames::paper(),
            'paper_id',
            Paper::class
        );
    }

    /**
     * Get the post type this repository handles
     *
     * @return string Post type slug
     */
    public function getPostType(): string
    {
        return 'paper';
    }

    /**
     * Get allowed filter columns for papers
     *
     * @return string[] Array of column names that can be used for filtering
     */
    protected function allowedFilterColumns(): array
    {
        return ['year', 'language', 'paper_type', 'magazine'];
    }

    /**
     * Insert or update a paper entity
     *
     * Performs an upsert operation (INSERT ... ON DUPLICATE KEY UPDATE) to save paper data.
     * Uses snake_case column names to match the database schema.
     *
     * @param BaseEntity $entity Paper entity to save
     * @return bool True on success, false on failure
     * @throws \InvalidArgumentException If entity is not a Paper instance
     */
    public function upsertMain(BaseEntity $entity): bool
    {
        if (!$entity instanceof Paper) {
            throw new \InvalidArgumentException('Entity must be an instance of Paper');
        }

        $data      = $entity->toArray();
        $now       = current_time('mysql');
        $createdAt = $entity->getCreatedAt() ?: $now;

        // Ensure the data field is stored as a JSON string
        $jsonData = is_array($data['data']) ? wp_json_encode($data['data']) : $data['data'];

        $sql = "INSERT INTO {$this->table}
            (paper_id, title, slug, post_status, data, magazine, paper_type, language, year, like_count, view_count, created_at, updated_at)
            VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            slug = VALUES(slug),
            post_status = VALUES(post_status),
            data = VALUES(data),
            magazine = VALUES(magazine),
            paper_type = VALUES(paper_type),
            language = VALUES(language),
            year = VALUES(year),
            like_count = VALUES(like_count),
            view_count = VALUES(view_count),
            updated_at = VALUES(updated_at)";

        $preparedSql = $this->db->prepare(
            $sql,
            $entity->getId(),
            $data['title'],
            $data['slug'],
            $data['post_status'],
            $jsonData,
            $data['magazine'] ?? '',
            $data['paper_type'] ?? '',
            $data['language'] ?? '',
            $data['year'] ?? '',
            $data['like_count'] ?? 0,
            $data['view_count'] ?? 0,
            $createdAt,
            $now
        );

        $this->db->hide_errors();
        $result = $this->db->query($preparedSql);

        if ($result === false) {
            error_log('Paper sync SQL error: ' . $this->db->last_error);
        }

        return $result !== false;
    }
}
