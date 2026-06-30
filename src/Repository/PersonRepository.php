<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Database\TableNames;
use InteractivityDocs\Models\BaseEntity;
use InteractivityDocs\Models\Person;

defined('ABSPATH') || exit;

/**
 * Person Repository
 *
 * Handles database operations for Person entities, including upsert, filtering, pagination,
 * and count recalculation for related papers and books.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
class PersonRepository extends BasePostRepository
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
            TableNames::person(),
            'person_id',
            Person::class
        );
    }

    /**
     * Get the post type this repository handles
     *
     * @return string Post type slug
     */
    public function getPostType(): string
    {
        return 'person';
    }

    /**
     * Get allowed filter columns for persons
     *
     * @return string[] Array of column names that can be used for filtering
     */
    protected function allowedFilterColumns(): array
    {
        return ['gender'];
    }

    /**
     * Insert or update a person entity
     *
     * Performs an upsert operation (INSERT ... ON DUPLICATE KEY UPDATE) to save person data.
     * Uses snake_case column names to match the database schema.
     *
     * @param BaseEntity $entity Person entity to save
     * @return bool True on success, false on failure
     * @throws \InvalidArgumentException If entity is not a Person instance
     */
    public function upsertMain(BaseEntity $entity): bool
    {
        if (!$entity instanceof Person) {
            throw new \InvalidArgumentException('Entity must be an instance of Person');
        }

        $data      = $entity->toArray();
        $now       = current_time('mysql');
        $createdAt = $entity->getCreatedAt() ?: $now;

        $sql = "INSERT INTO {$this->table}
            (person_id, title, slug, post_status, gender, image, paper_count, book_count, like_count, view_count, created_at, updated_at)
            VALUES (%d, %s, %s, %s, %s, %s, %d, %d, %d, %d, %s, %s)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            slug = VALUES(slug),
            post_status = VALUES(post_status),
            gender = VALUES(gender),
            image = VALUES(image),
            paper_count = VALUES(paper_count),
            book_count = VALUES(book_count),
            like_count = VALUES(like_count),
            view_count = VALUES(view_count),
            updated_at = VALUES(updated_at)";

        $preparedSql = $this->db->prepare(
            $sql,
            $entity->getId(),
            $data['title'],
            $data['slug'],
            $data['post_status'],
            $data['gender'] ?? '',
            $data['image'] ?? '',
            $data['paper_count'] ?? 0,
            $data['book_count'] ?? 0,
            $data['like_count'] ?? 0,
            $data['view_count'] ?? 0,
            $createdAt,
            $now
        );

        $this->db->hide_errors();
        $result = $this->db->query($preparedSql);

        if ($result === false) {
            error_log('Person sync SQL error: ' . $this->db->last_error);
        }

        return $result !== false;
    }

    /**
     * Recalculate paper or book counts for persons
     *
     * Recalculates the paper_count or book_count for specified persons by counting
     * actual relationships in the relation table, ensuring data consistency.
     *
     * @param int[]  $personIds Array of person IDs to recalculate counts for
     * @param string $postType  Post type ('paper' or 'book')
     * @return void
     * @throws \InvalidArgumentException If post type is not supported
     */
    public function recalculateCounts(array $personIds, string $postType): void
    {
        if (empty($personIds)) {
            return;
        }

        // Whitelist post type to prevent SQL injection on dynamic identifiers
        if (!in_array($postType, ['paper', 'book'], true)) {
            throw new \InvalidArgumentException('Unsupported post type: ' . $postType);
        }

        $prefix         = $this->db->prefix;
        $personTable    = $prefix . 'person';
        $relationTable  = $prefix . $postType . '_person';
        $countColumn    = $postType . '_count';
        $postColumn     = $postType . '_id';

        // Sanitize IDs to integers and build a safe placeholder list
        $idsPlaceholder = implode(',', array_map('intval', $personIds));

        // Recalculate counts by directly querying the relation table.
        // This ensures accuracy instead of relying on incremental updates.
        $query = "
            UPDATE {$personTable} p
            LEFT JOIN (
                SELECT person_id, COUNT(*) AS total
                FROM {$relationTable}
                WHERE {$postColumn} IS NOT NULL
                GROUP BY person_id
            ) rel ON p.person_id = rel.person_id
            SET p.{$countColumn} = COALESCE(rel.total, 0)
            WHERE p.person_id IN ({$idsPlaceholder})
        ";

        $this->db->query($query);
    }
}
