<?php

declare(strict_types=1);

namespace InteractivityDocs\Rest\Config;

defined('ABSPATH') || exit;

/**
 * Sort configuration for REST API endpoints
 *
 * Defines allowed sort options for each post type and provides
 * utilities for resolving and validating sort parameters.
 */
final class SortConfig
{
    /**
     * Get sort map for all post types
     *
     * Each sort key maps to [column, direction] tuple
     *
     * @return array<string, array<string, array{0: string, 1: 'ASC'|'DESC'}>>
     */
    public static function sortMap(): array
    {
        return [
            'paper' => [
                'latest'          => ['created_at', 'DESC'],
                'oldest'          => ['created_at', 'ASC'],
                'title_asc'       => ['title', 'ASC'],
                'title_desc'      => ['title', 'DESC'],
                'view_count_desc' => ['view_count', 'DESC'],
                'like_count_desc' => ['like_count', 'DESC'],
                'year_desc'       => ['year', 'DESC'],
                'year_asc'        => ['year', 'ASC'],
            ],
            'book' => [
                'latest'          => ['created_at', 'DESC'],
                'oldest'          => ['created_at', 'ASC'],
                'title_asc'       => ['title', 'ASC'],
                'title_desc'      => ['title', 'DESC'],
                'view_count_desc' => ['view_count', 'DESC'],
                'like_count_desc' => ['like_count', 'DESC'],
                'year_desc'       => ['year', 'DESC'],
                'year_asc'        => ['year', 'ASC'],
            ],
            'person' => [
                'latest'           => ['created_at', 'DESC'],
                'oldest'           => ['created_at', 'ASC'],
                'title_asc'        => ['title', 'ASC'],
                'title_desc'       => ['title', 'DESC'],
                'view_count_desc'  => ['view_count', 'DESC'],
                'like_count_desc'  => ['like_count', 'DESC'],
                'paper_count_desc' => ['paper_count', 'DESC'],
                'paper_count_asc'  => ['paper_count', 'ASC'],
                'book_count_desc'  => ['book_count', 'DESC'],
                'book_count_asc'   => ['book_count', 'ASC'],
            ],
        ];
    }

    /**
     * Get allowed sorts for a specific post type
     *
     * @param string $postType
     * @return array<string, array{0: string, 1: 'ASC'|'DESC'}>
     */
    public static function allowedSorts(string $postType): array
    {
        return self::sortMap()[$postType] ?? [];
    }

    /**
     * Get default sort for a post type
     *
     * @param string $postType
     * @return array{0: string, 1: 'ASC'|'DESC'}
     */
    public static function defaultSort(string $postType): array
    {
        $defaults = [
            'paper'  => ['created_at', 'DESC'],
            'book'   => ['created_at', 'DESC'],
            'person' => ['paper_count', 'DESC'],
        ];

        return $defaults[$postType] ?? ['created_at', 'DESC'];
    }

    /**
     * Resolve sort key to [column, direction] tuple
     *
     * Returns the resolved sort configuration for the given sort key,
     * or falls back to the default sort if key is invalid or null.
     *
     * @param string $postType
     * @param string|null $sortKey
     * @return array{0: string, 1: 'ASC'|'DESC'}
     */
    public static function resolve(string $postType, ?string $sortKey): array
    {
        $sorts = self::allowedSorts($postType);

        if ($sortKey && isset($sorts[$sortKey])) {
            return $sorts[$sortKey];
        }

        return self::defaultSort($postType);
    }

    /**
     * Check if sort key is valid for post type
     *
     * @param string $postType
     * @param string $sortKey
     * @return bool
     */
    public static function isAllowedSort(string $postType, string $sortKey): bool
    {
        return isset(self::allowedSorts($postType)[$sortKey]);
    }

    /**
     * Get all allowed sort keys for a post type
     *
     * Useful for validation and API documentation
     *
     * @param string $postType
     * @return string[]
     */
    public static function allowedSortKeys(string $postType): array
    {
        return array_keys(self::allowedSorts($postType));
    }

    /**
     * Check if resolved column is allowed for post type
     *
     * @param string $postType
     * @param string $column
     * @return bool
     */
    public static function isAllowedResolvedColumn(string $postType, string $column): bool
    {
        $columns = array_map(
            static fn(array $sort) => $sort[0],
            self::allowedSorts($postType)
        );

        $defaultColumn = self::defaultSort($postType)[0];

        return in_array($column, $columns, true) || $column === $defaultColumn;
    }

    /**
     * Check if sort direction is valid
     *
     * @param string $direction
     * @return bool
     */
    public static function isAllowedDirection(string $direction): bool
    {
        return in_array(strtoupper($direction), ['ASC', 'DESC'], true);
    }
}
