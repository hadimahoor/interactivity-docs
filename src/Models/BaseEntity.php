<?php

declare(strict_types=1);

namespace InteractivityDocs\Models;

defined('ABSPATH') || exit;

/**
 * Base Entity
 *
 * Abstract base class for all entity models.
 * Provides common properties and methods for database entities.
 *
 * @package InteractivityDocs\Models
 * @since 1.0.0
 */
abstract class BaseEntity
{
    /**
     * Entity ID
     *
     * @var int|null
     */
    protected ?int $id;

    /**
     * Entity title
     *
     * @var string
     */
    protected string $title;

    /**
     * URL-friendly slug
     * @var string
     */
    protected string $slug;

    /**
     * Post status (publish, draft, etc.)
     *
     * @var string
     */
    protected string $postStatus;

    /**
     * Number of likes
     * @var int
     */
    protected int $likeCount;

    /**
     * Number of views
     *
     * @var int
     */
    protected int $viewCount;

    /**
     * Creation timestamp
     *
     * @var string
     */
    protected string $createdAt;

    /**
     * Last update timestamp
     *
     * @var string
     */
    protected string $updatedAt;

    /**
     * Additional JSON data
     *
     * @var array
     */
    protected array $data;

    /**
     * Constructor
     * @param array $row Database row data
     */
    /**
     * Constructor
     * @param array $row Database row data
     */
    public function __construct(array $row = [])
    {
        $this->id         = isset($row['id']) ? (int)$row['id'] : null;
        $this->title      = (string)($row['title'] ?? '');
        $this->slug       = (string)($row['slug'] ?? '');
        $this->postStatus = (string)($row['post_status'] ?? 'publish');
        $this->likeCount  = (int)($row['like_count'] ?? 0);
        $this->viewCount  = (int)($row['view_count'] ?? 0);
        $this->createdAt  = (string)($row['created_at'] ?? date('Y-m-d H:i:s'));
        $this->updatedAt  = (string)($row['updated_at'] ?? date('Y-m-d H:i:s'));

        $this->data = is_string($row['data'] ?? null)
            ? (json_decode($row['data'], true) ?: [])
            : ($row['data'] ?? []);
    }


    /**
     * Convert entity to database array
     * @return array Database-ready array with snake_case keys
     */
    public function toArray(): array
    {
        $data = [
            'title'       => $this->title,
            'slug'        => $this->slug,
            'post_status' => $this->postStatus,
            'like_count'  => $this->likeCount,
            'view_count'  => $this->viewCount,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
            'data'        => wp_json_encode($this->data, JSON_UNESCAPED_UNICODE),
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        return $data;
    }

    // --- Getters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getPostStatus(): string
    {
        return $this->postStatus;
    }

    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getData(): array
    {
        return $this->data;
    }

    // --- Seters ---

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function setPostStatus(string $status): void
    {
        $this->postStatus = $status;
    }

    public function setLikeCount(int $count): void
    {
        $this->likeCount = $count;
    }

    public function setViewCount(int $count): void
    {
        $this->viewCount = $count;
    }

    public function setCreatedAt(string $date): void
    {
        $this->createdAt = $date;
    }

    public function setUpdatedAt(string $date): void
    {
        $this->updatedAt = $date;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
