<?php

declare(strict_types=1);

namespace InteractivityDocs\Database;

defined('ABSPATH') || exit;

/**
 * Schema Manager
 *
 * Manages database schema creation for custom tables used to optimize
 * queries for papers, books, persons, and their relationships.
 *
 * Tables store denormalized data synced from WordPress posts for faster
 * filtering and aggregation in the frontend.
 *
 * @package InteractivityDocs\Database
 * @since 1.0.0
 */
final class SchemaManager
{
    /**
     * Create all custom database tables
     *
     * Uses dbDelta() to safely create or update tables without data loss.
     *
     * @return void
     */
    public function createTables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($this->getPaperSchema());
        dbDelta($this->getBookSchema());
        dbDelta($this->getPersonSchema());
        dbDelta($this->getPaperPersonSchema());
        dbDelta($this->getBookPersonSchema());
    }

    /**
     * Drop all plugin tables.
     *
     * Drops tables in dependency order: junction tables first (to avoid foreign-key-like
     * constraints in reporting/validation logic), then entity tables.
     *
     * @return void
     */
    public function dropTables(): void
    {
        global $wpdb;

        // Junction tables first, then entity tables
        $tables = [
            TableNames::paperPerson(),
            TableNames::bookPerson(),
            TableNames::paper(),
            TableNames::book(),
            TableNames::person(),
        ];

        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Get all plugin table names keyed by logical name.
     *
     * @return array<string, string> Map of logical name => full table name
     */
    public function getTableNames(): array
    {
        return [
            'paper'        => TableNames::paper(),
            'book'         => TableNames::book(),
            'person'       => TableNames::person(),
            'paper_person' => TableNames::paperPerson(),
            'book_person'  => TableNames::bookPerson(),
        ];
    }

    /**
     * Report existence and row count for each table.
     *
     * @return array<int, array{name: string, table: string, exists: bool, rows: int}>
     */
    public function getStatus(): array
    {
        global $wpdb;

        $status = [];

        foreach ($this->getTableNames() as $name => $table) {
            $exists = (bool) $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $table)
            );

            $rows = 0;
            if ($exists) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            }

            $status[] = [
                'name'   => $name,
                'table'  => $table,
                'exists' => $exists,
                'rows'   => $rows,
            ];
        }

        return $status;
    }

    /**
     * Get the WordPress database charset and collation
     *
     * @return string Charset collation string (e.g., "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
     */
    private function getCharset(): string
    {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }

    /**
     * Get the paper table schema
     *
     * Stores denormalized paper data with taxonomy terms and metadata.
     *
     * The `data` column (JSON) holds all entity-related information needed for
     * frontend display, filtering, and search—reducing server requests by providing
     * complete context in a single query.
     *
     * Indexes optimize common filter operations (status, year, language, type, magazine).
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getPaperSchema(): string
    {
        $table = TableNames::paper();
        return "CREATE TABLE {$table} (
            paper_id BIGINT(20) UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            slug VARCHAR(200) NOT NULL,
            post_status VARCHAR(20) NOT NULL DEFAULT 'publish',
            data JSON NULL COMMENT 'Complete entity data for frontend filtering and display',
            magazine VARCHAR(255) DEFAULT '',
            paper_type VARCHAR(255) DEFAULT '',
            language VARCHAR(100) DEFAULT '',
            year VARCHAR(20) DEFAULT '',
            like_count BIGINT(20) UNSIGNED DEFAULT 0,
            view_count BIGINT(20) UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (paper_id),
            KEY slug (slug),
            KEY idx_status (post_status),
            KEY idx_year (year),
            KEY idx_language (language),
            KEY idx_paper_type (paper_type),
            KEY idx_magazine (magazine)
        ) {$this->getCharset()};";
    }

    /**
     * Get the book table schema
     *
     * Stores denormalized book data with taxonomy terms and metadata.
     *
     * The `data` column (JSON) holds all entity-related information needed for
     * frontend display, filtering, and search—reducing server requests by providing
     * complete context in a single query.
     *
     * Indexes optimize common filter operations (status, year, language, type, publication).
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getBookSchema(): string
    {
        $table = TableNames::book();
        return "CREATE TABLE {$table} (
            book_id BIGINT(20) UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            slug VARCHAR(200) NOT NULL,
            post_status VARCHAR(20) NOT NULL,
            data JSON NULL COMMENT 'Complete entity data for frontend filtering and display',
            publication VARCHAR(255) DEFAULT '',
            book_type VARCHAR(255) DEFAULT '',
            language VARCHAR(255) DEFAULT '',
            image VARCHAR(255) DEFAULT '',
            year VARCHAR(20) DEFAULT '',
            like_count BIGINT(20) UNSIGNED DEFAULT 0,
            view_count BIGINT(20) UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (book_id),
            KEY slug (slug),
            KEY idx_status (post_status),
            KEY idx_year (year),
            KEY idx_language (language),
            KEY idx_book_type (book_type),
            KEY idx_publication (publication)
        ) {$this->getCharset()};";
    }

    /**
     * Get the person table schema
     *
     * Stores denormalized person data with aggregated counts.
     *
     * The `data` column (JSON) holds all entity-related information needed for
     * frontend display, filtering, and search—reducing server requests by providing
     * complete context in a single query.
     *
     * Indexes optimize common filter operations (status, gender).
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getPersonSchema(): string
    {
        $table = TableNames::person();
        return "CREATE TABLE {$table} (
            person_id BIGINT(20) UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            slug VARCHAR(200) NOT NULL,
            post_status VARCHAR(20) NOT NULL,
            gender VARCHAR(60) DEFAULT '',
            data JSON NULL COMMENT 'Complete entity data for frontend filtering and display',
            paper_count BIGINT(20) UNSIGNED DEFAULT 0,
            book_count BIGINT(20) UNSIGNED DEFAULT 0,
            like_count BIGINT(20) UNSIGNED DEFAULT 0,
            view_count BIGINT(20) UNSIGNED DEFAULT 0,
            image VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (person_id),
            KEY slug (slug),
            KEY idx_status (post_status),
            KEY idx_gender (gender)
        ) {$this->getCharset()};";
    }

    /**
     * Get the paper-person relationship table schema
     *
     * Many-to-many junction table linking papers to their authors/contributors.
     * Composite primary key on (paper_id, person_id) automatically indexes paper_id lookups.
     * Separate index on person_id enables efficient reverse lookups (all papers by a person).
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getPaperPersonSchema(): string
    {
        $table = TableNames::paperPerson();
        return "CREATE TABLE {$table} (
            paper_id BIGINT(20) UNSIGNED NOT NULL,
            person_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (paper_id, person_id),
            KEY idx_person_papers (person_id)
        ) {$this->getCharset()};";
    }

    /**
     * Get the book-person relationship table schema
     *
     * Many-to-many junction table linking books to their authors/contributors.
     * Composite primary key on (book_id, person_id) automatically indexes book_id lookups.
     * Separate index on person_id enables efficient reverse lookups (all books by a person).
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getBookPersonSchema(): string
    {
        $table = TableNames::bookPerson();
        return "CREATE TABLE {$table} (
            book_id BIGINT(20) UNSIGNED NOT NULL,
            person_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (book_id, person_id),
            KEY idx_person_books (person_id)
        ) {$this->getCharset()};";
    }
}
