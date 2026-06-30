<?php

declare(strict_types=1);

namespace InteractivityDocs\Models;

defined('ABSPATH') || exit;

/**
 * Person Entity
 *
 * Represents an author or contributor with gender, publication counts, and profile image.
 *
 * @package InteractivityDocs\Models
 * @since 1.0.0
 */
class Person extends BaseEntity
{
    /**
     * Gender of the person
     *
     * @var string
     */
    protected string $gender;

    /**
     * Number of papers authored
     *
     * @var int
     */
    protected int $paperCount;

    /**
     * Number of books authored
     *
     * @var int
     */
    protected int $bookCount;

    /**
     * Profile image URL or path
     *
     * @var string
     */
    protected string $image;

    /**
     * Constructor
     *
     * @param array $row Database row data
     */
    public function __construct(array $row = [])
    {
        parent::__construct($row);

        $this->gender     = (string)($row['gender'] ?? '');
        $this->paperCount = (int)($row['paper_count'] ?? 0);
        $this->bookCount  = (int)($row['book_count'] ?? 0);
        $this->image      = (string)($row['image'] ?? '');
    }

    /**
     * Convert entity to database array
     *
     * @return array Database-ready array with snake_case keys
     */
    public function toArray(): array
    {
        // Sync data array with model values if needed
        // $this->data['some_key'] = $this->someVariable;

        // Call parent (BaseEntity) which handles data to JSON conversion
        $arr = parent::toArray();

        // Add model-specific fields to database output array
        $arr['gender']      = $this->gender;
        $arr['paper_count'] = $this->paperCount;
        $arr['book_count']  = $this->bookCount;
        $arr['image']       = $this->image;

        return $arr;
    }

    // --- Getters ---

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getPaperCount(): int
    {
        return $this->paperCount;
    }

    public function getBookCount(): int
    {
        return $this->bookCount;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    // --- Setters ---

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function setPaperCount(int $count): void
    {
        $this->paperCount = $count;
    }

    public function setBookCount(int $count): void
    {
        $this->bookCount = $count;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }
}
