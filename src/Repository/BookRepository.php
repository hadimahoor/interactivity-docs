<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Database\TableNames;
use InteractivityDocs\Models\BaseEntity;
use InteractivityDocs\Models\Book;

defined('ABSPATH') || exit;

/**
 * Book Repository
 *
 * Handles database operations for Book entities, including upsert, filtering, and pagination.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
class BookRepository extends BasePostRepository
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
            TableNames::book(),
            'book_id',
            Book::class
        );
    }

    /**
     * Get the post type this repository handles
     *
     * @return string Post type slug
     */
    public function getPostType(): string
    {
        return 'book';
    }

    /**
     * Get allowed filter columns for books
     *
     * @return string[] Array of column names that can be used for filtering
     */
    protected function allowedFilterColumns(): array
    {
        return ['year', 'language', 'book_type', 'publication'];
    }

    /**
     * Insert or update a book entity
     *
     * Performs an upsert operation (INSERT ... ON DUPLICATE KEY UPDATE) to save book data.
     * Uses snake_case column names to match the database schema.
     *
     * @param BaseEntity $entity Book entity to save
     * @return bool True on success, false on failure
     * @throws \InvalidArgumentException If entity is not a Book instance
     */
    public function upsertMain(BaseEntity $entity): bool
    {
        if (!$entity instanceof Book) {
            throw new \InvalidArgumentException('Entity must be an instance of Book');
        }

        $data      = $entity->toArray();
        $now       = current_time('mysql');
        $createdAt = $entity->getCreatedAt() ?: $now;

        // Ensure the data field is stored as a JSON string
        $jsonData = is_array($data['data']) ? wp_json_encode($data['data']) : $data['data'];

        $sql = "INSERT INTO {$this->table}
            (book_id, title, slug, post_status, data, publication, book_type, language, image, year, like_count, view_count, created_at, updated_at)
            VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            slug = VALUES(slug),
            post_status = VALUES(post_status),
            data = VALUES(data),
            publication = VALUES(publication),
            book_type = VALUES(book_type),
            language = VALUES(language),
            image = VALUES(image),
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
            $data['publication'] ?? '',
            $data['book_type'] ?? '',
            $data['language'] ?? '',
            $data['image'] ?? '',
            $data['year'] ?? '',
            $data['like_count'] ?? 0,
            $data['view_count'] ?? 0,
            $createdAt,
            $now
        );

        $this->db->hide_errors();
        $result = $this->db->query($preparedSql);

        if ($result === false) {
            error_log('Book sync SQL error: ' . $this->db->last_error);
        }

        return $result !== false;
    }
}
