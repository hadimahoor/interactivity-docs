<?php

declare(strict_types=1);

namespace InteractivityDocs\Sync;

use InteractivityDocs\Repository\RepositoryFactoryInterface;
use InteractivityDocs\Models\BaseEntity;
use InteractivityDocs\Models\Paper;
use InteractivityDocs\Models\Book;
use InteractivityDocs\Models\Person;
use Psr\Log\LoggerInterface;

defined('ABSPATH') || exit;

/**
 * Manages synchronization between WordPress posts and custom database tables.
 *
 * This class handles the transformation and persistence of WordPress posts
 * (paper, book, person) into their corresponding database representations,
 * preserving relationships, taxonomies, and metadata while maintaining
 * statistical counters like views and likes.
 */
class PostSyncManager
{
    private RepositoryFactoryInterface $repositoryFactory;
    private ?LoggerInterface $logger;

    /**
     * Supported post types for synchronization.
     */
    private const SUPPORTED_POST_TYPES = ['paper', 'book', 'person'];

    public function __construct(RepositoryFactoryInterface $repositoryFactory, ?LoggerInterface $logger = null)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->logger = $logger;
    }

    /**
     * Synchronizes a WordPress post into its custom database table.
     *
     * Called by SyncCoordinator during an atomic sync. It fetches the existing
     * database record (if any) to preserve counters, builds a fresh model from
     * the post data, and performs an upsert operation.
     *
     * @param int $post_id The post ID being saved
     * @param \WP_Post $post The post object
     */
    public function syncPost(int $post_id, \WP_Post $post): void
    {
        // Validate post type
        if (!in_array($post->post_type, self::SUPPORTED_POST_TYPES, true)) {
            return;
        }

        // Skip revisions and autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        try {
            $repo = $this->repositoryFactory->createRepositoryForPostType($post->post_type);
            if (!$repo) {
                $this->logWarning("No repository found for post type: {$post->post_type}");
                return;
            }

            // Fetch existing record to preserve counters (like_count, view_count)
            $existing = $repo->fetchForSync($post_id);

            // Build new entity from post data
            $newEntity = $this->createModelFromPost($post, $existing);

            // Perform upsert
            $repo->upsertMain($newEntity);
        } catch (\Exception $e) {
            $this->logError("Failed to sync post {$post_id}: {$e->getMessage()}", [
                'post_id' => $post_id,
                'post_type' => $post->post_type,
                'exception' => $e
            ]);
        }
    }

    /**
     * Creates the appropriate model instance based on post type.
     *
     * @param \WP_Post $post The WordPress post object
     * @param BaseEntity|null $existing Existing database record, if any
     * @return BaseEntity The constructed model instance
     * @throws \InvalidArgumentException If post type is not supported
     */
    private function createModelFromPost(\WP_Post $post, ?BaseEntity $existing): BaseEntity
    {
        return match ($post->post_type) {
            'paper'  => $this->buildPaper($post, $existing),
            'book'   => $this->buildBook($post, $existing),
            'person' => $this->buildPerson($post, $existing),
            default  => throw new \InvalidArgumentException("Unknown post type: {$post->post_type}")
        };
    }

    /**
     * Builds a Paper model from a WordPress post.
     *
     * @param \WP_Post $post The post object
     * @param BaseEntity|null $existing Existing record for preserving counters
     * @return Paper
     */
    private function buildPaper(\WP_Post $post, ?BaseEntity $existing): Paper
    {
        // Extract taxonomy and relationship data
        $terms = [
            'year'       => $this->getTermData($post->ID, 'year_tax'),
            'language'   => $this->getTermData($post->ID, 'language_tax'),
            'magazine'   => $this->getTermData($post->ID, 'magazine_tax'),
            'paper_type' => $this->getTermData($post->ID, 'papertype_tax'),
            'authors'    => $this->getAuthorsData($post->ID, 'author'),
        ];

        $paper = new Paper([]);

        // Populate database columns for search and filtering
        $this->fillBaseFields($paper, $post, $existing, $terms);
        $paper->setMagazine($terms['magazine']['name'] ?? '');
        $paper->setPaperType($terms['paper_type']['name'] ?? '');
        $paper->setAuthors($terms['authors']);

        // Prepare JSON snapshot for fast frontend rendering
        $snapshot = [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'permalink'  => get_permalink($post->ID),
            'year'       => $terms['year'],
            'language'   => $terms['language'],
            'magazine'   => $terms['magazine'],
            'paper_type' => $terms['paper_type'],
            'authors'    => $terms['authors'],
        ];
        $paper->setData($snapshot);

        return $paper;
    }

    /**
     * Builds a Book model from a WordPress post.
     *
     * @param \WP_Post $post The post object
     * @param BaseEntity|null $existing Existing record for preserving counters
     * @return Book
     */
    private function buildBook(\WP_Post $post, ?BaseEntity $existing): Book
    {
        // Extract taxonomy and relationship data
        $terms = [
            'year'        => $this->getTermData($post->ID, 'year_tax'),
            'language'    => $this->getTermData($post->ID, 'language_tax'),
            'publication' => $this->getTermData($post->ID, 'publication_tax'),
            'book_type'   => $this->getTermData($post->ID, 'booktype_tax'),
            'authors'     => $this->getAuthorsData($post->ID, 'author'),
        ];

        $book = new Book([]);

        // Populate database columns for search and filtering
        $this->fillBaseFields($book, $post, $existing, $terms);
        $book->setPublication($terms['publication']['name'] ?? '');
        $book->setBookType($terms['book_type']['name'] ?? '');
        $book->setAuthors($terms['authors']);

        // Prepare JSON snapshot for fast frontend rendering
        $snapshot = [
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'permalink'   => get_permalink($post->ID),
            'image'       => $book->getImage(),
            'year'        => $terms['year'],
            'language'    => $terms['language'],
            'publication' => $terms['publication'],
            'book_type'   => $terms['book_type'],
            'authors'     => $terms['authors'],
        ];
        $book->setData($snapshot);

        return $book;
    }

    /**
     * Builds a Person model from a WordPress post.
     *
     * @param \WP_Post $post The post object
     * @param BaseEntity|null $existing Existing record for preserving counters
     * @return Person
     */
    private function buildPerson(\WP_Post $post, ?BaseEntity $existing): Person
    {
        // Extract taxonomy data
        $terms = [
            'gender' => $this->getTermData($post->ID, 'gender_tax'),
        ];

        $person = new Person([]);

        // Populate base fields through shared method
        $this->fillBaseFields($person, $post, $existing, $terms);

        // Preserve relationship counters (just like likes/views)
        if ($existing instanceof Person) {
            $person->setPaperCount($existing->getPaperCount());
            $person->setBookCount($existing->getBookCount());
        }

        // Set person-specific fields
        $person->setGender($terms['gender']['name'] ?? '');

        // Prepare snapshot for JSON column (data field)
        // Used for displaying in author/faculty lists without additional queries
        $snapshot = [
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'permalink' => get_permalink($post->ID),
            'image'     => $person->getImage(),
            'gender'    => $terms['gender'],
            // Statistical data can be included here
            'metrics'   => [
                'likes'  => $person->getLikeCount(),
                'views'  => $person->getViewCount(),
                'papers' => $person->getPaperCount(),
                'books'  => $person->getBookCount()
            ]
        ];
        $person->setData($snapshot);

        return $person;
    }

    /**
     * Populates common base fields shared across all entity types.
     *
     * This method handles:
     * - Basic post data (ID, title, slug, status)
     * - Common taxonomy fields (year, language)
     * - Image URLs
     * - Preservation of existing counters (likes, views)
     * - Timestamp management
     *
     * @param BaseEntity $model The model instance to populate
     * @param \WP_Post $post The WordPress post
     * @param BaseEntity|null $existing Existing record to preserve counters
     * @param array $terms Extracted taxonomy terms
     */
    private function fillBaseFields(BaseEntity $model, \WP_Post $post, ?BaseEntity $existing, array $terms): void
    {
        $model->setId($post->ID);
        $model->setTitle($post->post_title);
        $model->setSlug($post->post_name);
        $model->setPostStatus($post->post_status);

        // Set image if the model supports it
        if (method_exists($model, 'setImage')) {
            $model->setImage(get_the_post_thumbnail_url($post->ID) ?: "");
        }

        // Set common taxonomy fields
        if (!empty($terms['year']['name'])) {
            $model->setYear($terms['year']['name']);
        }
        if (!empty($terms['language']['name'])) {
            $model->setLanguage($terms['language']['name']);
        }

        // Preserve existing counters or initialize to zero
        if ($existing) {
            $model->setLikeCount($existing->getLikeCount());
            $model->setViewCount($existing->getViewCount());
            $model->setCreatedAt($existing->getCreatedAt());
        } else {
            $model->setCreatedAt(current_time('mysql'));
        }

        $model->setUpdatedAt(current_time('mysql'));
    }

    /**
     * Retrieves taxonomy term data for a post.
     *
     * Returns the first term's name and URL, or empty strings if no term exists.
     *
     * @param int $post_id The post ID
     * @param string $taxonomy The taxonomy name
     * @return array{name: string, url: string}
     */
    private function getTermData(int $post_id, string $taxonomy): array
    {
        $terms = get_the_terms($post_id, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return ['name' => '', 'url' => ''];
        }

        return [
            'name' => $terms[0]->name,
            'url'  => get_term_link($terms[0]) ?: ''
        ];
    }

    /**
     * Retrieves author data from an ACF relationship field.
     *
     * Returns an array of author information including ID, name, URL, and image.
     *
     * @param int $post_id The post ID
     * @param string $acf_field The ACF field name
     * @return array<int, array{id: int, name: string, url: string, image: string}>
     */
    private function getAuthorsData(int $post_id, string $acf_field): array
    {
        $author_ids = get_field($acf_field, $post_id);
        if (!$author_ids) {
            return [];
        }

        $ids = is_array($author_ids) ? $author_ids : [$author_ids];

        return array_map(function ($id) {
            return [
                'id'    => (int)$id,
                'name'  => get_the_title($id),
                'url'   => get_the_permalink($id),
                'image' => get_the_post_thumbnail_url($id) ?: ""
            ];
        }, $ids);
    }

    /**
     * Handles post deletion by removing the corresponding database record.
     *
     * @param \WP_Post $post The deleted post
     */
    public function deletePost(\WP_Post $post): void
    {
        if (!in_array($post->post_type, self::SUPPORTED_POST_TYPES, true)) {
            return;
        }

        try {
            $repo = $this->repositoryFactory->createRepositoryForPostType($post->post_type);
            if ($repo) {
                $repo->delete($post->ID);
            }
        } catch (\Exception $e) {
            $this->logError("Failed to delete post {$post->ID}: {$e->getMessage()}", [
                'post_id' => $post->ID,
                'post_type' => $post->post_type,
                'exception' => $e
            ]);
        }
    }

    /**
     * Logs a warning message if a logger is configured.
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * Logs an error message if a logger is configured.
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }
}
