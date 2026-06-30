<?php

declare(strict_types=1);

namespace InteractivityDocs\Models;

defined('ABSPATH') || exit;

/**
 * Paper Entity
 *
 * Represents a research paper or article with authors, magazine, type, and metadata.
 *
 * @package InteractivityDocs\Models
 * @since 1.0.0
 */
class Paper extends BaseEntity
{
    /**
     * Magazine or journal name
     *
     * @var string
     */
    protected string $magazine;

    /**
     * Type of paper (research, review, etc.)
     * @var string
     */
    protected string $paperType;

    /**
     * Language of the paper
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

        $this->magazine  = (string)($row['magazine'] ?? '');
        $this->paperType = (string)($row['paper_type'] ?? '');
        $this->language  = (string)($row['language'] ?? '');
        $this->year      = (string)($row['year'] ?? '');

        // Get authors from data field decoded in BaseEntity
        $this->authors = isset($this->data['authors']) && is_array($this->data['authors'])
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
        $arr['magazine']   = $this->magazine;
        $arr['paper_type'] = $this->paperType;
        $arr['language']   = $this->language;
        $arr['year']       = $this->year;

        return $arr;
    }

    // --- Getters ---

    public function getMagazine(): string
    {
        return $this->magazine;
    }

    public function getPaperType(): string
    {
        return $this->paperType;
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

    // --- Seters ---

    public function setMagazine(string $magazine): void
    {
        $this->magazine = $magazine;
    }

    public function setPaperType(string $paperType): void
    {
        $this->paperType = $paperType;
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
