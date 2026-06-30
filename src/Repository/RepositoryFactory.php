<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Database\TableNames;

defined('ABSPATH') || exit;

/**
 * Repository Factory
 *
 * Central factory for creating repository instances.
 * Manages dependency injection and ensures consistent repository initialization.
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
final class RepositoryFactory
{
    private \wpdb $db;

    /**
     * Constructor
     *
     * @param \wpdb $db WordPress database instance
     */
    public function __construct(\wpdb $db)
    {
        $this->db = $db;
    }

    /**
     * Create a Paper repository instance
     *
     * @return PaperRepository
     */
    public function createPaperRepository(): PaperRepository
    {
        return new PaperRepository($this->db);
    }

    /**
     * Create a Book repository instance
     *
     * @return BookRepository
     */
    public function createBookRepository(): BookRepository
    {
        return new BookRepository($this->db);
    }

    /**
     * Create a Person repository instance
     *
     * @return PersonRepository
     */
    public function createPersonRepository(): PersonRepository
    {
        return new PersonRepository($this->db);
    }

    /**
     * Create a relation repository for managing many-to-many relationships
     *
     * @param string $type Relation type ('paper_person' or 'book_person')
     * @return RelationRepository
     * @throws \InvalidArgumentException If relation type is invalid
     */
    public function createRelationRepository(string $type): RelationRepository
    {
        return match ($type) {
            'paper_person' => new RelationRepository(
                $this->db,
                TableNames::paperPerson(),
                'paper_id',
                'person_id'
            ),
            'book_person' => new RelationRepository(
                $this->db,
                TableNames::bookPerson(),
                'book_id',
                'person_id'
            ),
            default => throw new \InvalidArgumentException("Invalid relation type: {$type}")
        };
    }

    /**
     * Create a repository based on WordPress post type
     *
     * Returns the appropriate repository for the given post type.
     * Returns null if the post type is not recognized.
     *
     * @param string $postType WordPress post type slug
     * @return PostRepositoryInterface|null Repository instance or null if post type is unknown
     */
    public function createRepositoryForPostType(string $postType): ?PostRepositoryInterface
    {
        return match ($postType) {
            'paper' => $this->createPaperRepository(),
            'book' => $this->createBookRepository(),
            'person' => $this->createPersonRepository(),
            default => null,
        };
    }
}
