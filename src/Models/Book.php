<?php

declare(strict_types=1);

namespace InteractivityDocs\Models;

defined('ABSPATH') || exit;

/**
 * Book Entity
 * Represents a book with publication info, type, authors, and metadata.
 *
 * @package InteractivityDocs\Models
 * @since 1.0.0
 */
class Book extends BaseEntity
{
    /**
     * Publication or publisher name
     *
     * @var string
     */
    protected string $publication;

    /**
     * Type of book (textbook, reference, etc.)
     *
     * @var string
     */
    protected string $bookType;

    /**
     * Image URL or path
     *
     * @var string
     */
    protected string $image;

    /**
     * Language of the book
     *
     * @var string
     */
    protected string $language;

    /**
     * Publication year
     *
     * @var string
     */
    protected string $year;

    /**
     * Array of author data (id, name, url, image)
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $authors;

    /**
     * Constructor
     * @param array $row Database row data
     */
    public function __construct(array $row = [])
    {
        parent::__construct($row);

        $this->publication = (string)($row['publication'] ?? '');
        $this->bookType    = (string)($row['book_type'] ?? '');
        $this->image       = (string)($row['image'] ?? '');
        $this->language    = (string)($row['language'] ?? '');
        $this->year        = (string)($row['year'] ? '');

        // Get authors from data field decoded in BaseEntity
        $this->authors = iset($this->data['authors']) && is_array($this->data['authors'])
            ? $this->data['authors']
            : [];
    }

    /**
     * Convert entity to database array
     *
     * @return array Database-ready array with snake_case keys
     */
    public function toArray(): array
    {
        // Sync data array with model values
        $this->data['authors'] = $this->authors;

        // Call parent (BaseEntity) which handles final data to JSON conversion
        $arr = parent::toArray();

        // Add model-specific fields to database output array
        $ar['publication'] = $this->publication;
        $arr['book_type']   = $this->bookType;
        $arr['image']       = $this->image;
        $arr['language']    = $this->language;
        $arr['year']        = $this->year;

        return $arr;
    }

    // --- Getters ---

    public function getPublication(): string
    {
        return $this->publication;
    }

    public function getBookType(): string
    {
        return $this->bookType;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    // --- Setters ---

    public function setPublication(string $publication): void
    {
        $this->publication = $publication;
    }

    public function setBookType(string $bookType): void
    {
        $this->bookType = $bookType;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function setYear(string $year): void
    {
        $this->year = $year;
    }

    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }
}
