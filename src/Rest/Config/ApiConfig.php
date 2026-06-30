<?php

declare(strict_types=1);

namespace InteractivityDocs\Rest\Config;

defined('ABSPATH') || exit;

final class ApiConfig
{
    public const REST_NAMESPACE = 'interactivity-docs/v1';

    public const DEFAULT_PAGE = 1;
    public const DEFAULT_PER_PAGE = 12;
    public const MAX_PER_PAGE = 100;
    public const DEFAULT_STATUS = 'publish';
    public const DEFAULT_SORT = 'latest';

    /**
     * Reserved WordPress parameters that need aliasing
     * Maps public API parameter names to internal filter keys
     */
    private const PARAMETER_ALIASES = [
        'filter_year' => 'year', // 'year' is reserved by WordPress
    ];

    /**
     * Allowed post types for API endpoints
     *
     * @return string[]
     */
    public static function allowedPostTypes(): array
    {
        return ['paper', 'book', 'person'];
    }

    /**
     * Taxonomy/filter map for each post type
     *
     * @return array<string, string[]>
     */
    public static function taxonomyMap(): array
    {
        return [
            'paper' => [
                'year',
                'language',
                'paper_type',
                'magazine',
            ],
            'book' => [
                'year',
                'language',
                'book_type',
                'publication',
            ],
            'person' => [
                'gender',
            ],
        ];
    }

    /**
     * Get allowed filters for a specific post type
     *
     * @param string $postType
     * @return string[]
     */
    public static function allowedFilters(string $postType): array
    {
        return self::taxonomyMap()[$postType] ?? [];
    }

    /**
     * Check if post type is allowed
     *
     * @param string $postType
     * @return bool
     */
    public static function isAllowedPostType(string $postType): bool
    {
        return in_array($postType, self::allowedPostTypes(), true);
    }

    /**
     * Check if filter is allowed for given post type
     *
     * @param string $postType
     * @param string $filter
     * @return bool
     */
    public static function isAllowedFilter(string $postType, string $filter): bool
    {
        return in_array($filter, self::allowedFilters($postType), true);
    }

/**
     * Extract and validate filters from request parameters
     *
     * Handles parameter aliasing for reserved WordPress query vars
     *
     * @param string $postType
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function extractAllowedFilters(string $postType, array $params): array
    {
        $allowedFilters = self::allowedFilters($postType);
        $extracted = [];

        foreach ($params as $key => $value) {
            $filterKey = self::PARAMETER_ALIASES[$key] ?? rtrim($key, '[]');

            if (!in_array($filterKey, $allowedFilters, true)) {
                continue;
            }

            $extracted[$filterKey] = is_array($value)
            ? array_map('sanitize_text_field', $value)
            : sanitize_text_field((string) $value);
        }

        return $extracted;
    }


    /**
     * Route arguments for docs endpoint
     *
     * @return array<string, array<string, mixed>>
     */
    public static function docsArgs(): array
    {
        return [
            'post_type' => [
                'required'          => true,
                'sanitize_callback' => [self::class, 'sanitizePostType'],
                'validate_callback' => [self::class, 'validatePostType'],
            ],
            'status' => [
                'required'          => false,
                'default'           => self::DEFAULT_STATUS,
                'sanitize_callback' => [self::class, 'sanitizeStatus'],
                'validate_callback' => [self::class, 'validateNonEmptyString'],
            ],
            'page' => [
                'required'          => false,
                'default'           => self::DEFAULT_PAGE,
                'sanitize_callback' => [self::class, 'sanitizePositiveInt'],
                'validate_callback' => [self::class, 'validatePositiveInt'],
            ],
            'per_page' => [
                'required'          => false,
                'default'           => self::DEFAULT_PER_PAGE,
                'sanitize_callback' => [self::class, 'sanitizePerPage'],
                'validate_callback' => [self::class, 'validatePerPage'],
            ],
            'sort' => [
                'required'          => false,
                'default'           => self::DEFAULT_SORT,
                'sanitize_callback' => [self::class, 'sanitizeSortKey'],
            ],
            'include_meta' => [
                'required'          => false,
                'default'           => true,
                'sanitize_callback' => [self::class, 'sanitizeBoolean'],
            ],
            'only_items' => [
                'required'          => false,
                'default'           => false,
                'sanitize_callback' => [self::class, 'sanitizeBoolean'],
            ],
            'only_meta' => [
                'required'          => false,
                'sanitize_callback' => [self::class, 'sanitizeCsvList'],
            ],
        ];
    }

    /**
     * Route arguments for facets endpoint
     *
     * @return array<string, array<string, mixed>>
     */
    public static function facetsArgs(): array
    {
        return [
            'post_type' => [
                'required'          => true,
                'sanitize_callback' => [self::class, 'sanitizePostType'],
                'validate_callback' => [self::class, 'validatePostType'],
            ],
            'status' => [
                'required'          => false,
                'default'           => self::DEFAULT_STATUS,
                'sanitize_callback' => [self::class, 'sanitizeStatus'],
                'validate_callback' => [self::class, 'validateNonEmptyString'],
            ],
            'limit' => [
                'required'          => false,
                'default'           => 200,
                'sanitize_callback' => [self::class, 'sanitizeLimit'],
                'validate_callback' => [self::class, 'validateLimit'],
            ],
        ];
    }

    public static function sanitizePostType(mixed $value): string
    {
        return sanitize_key((string) $value);
    }

    public static function validatePostType(mixed $value): bool
    {
        return is_string($value) && self::isAllowedPostType($value);
    }

    public static function sanitizeStatus(mixed $value): string
    {
        $value = sanitize_text_field((string) $value);
        return $value !== '' ? $value : self::DEFAULT_STATUS;
    }

    public static function validateNonEmptyString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    public static function sanitizePositiveInt(mixed $value): int
    {
        $value = absint($value);
        return $value > 0 ? $value : self::DEFAULT_PAGE;
    }

    public static function validatePositiveInt(mixed $value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }

    public static function sanitizePerPage(mixed $value): int
    {
        $value = absint($value);

        if ($value < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($value, self::MAX_PER_PAGE);
    }

    public static function validatePerPage(mixed $value): bool
    {
        return is_numeric($value)
            && (int) $value >= 1
            && (int) $value <= self::MAX_PER_PAGE;
    }

    public static function sanitizeLimit(mixed $value): int
    {
        $value = absint($value);
        return max(1, min(500, $value));
    }

    public static function validateLimit(mixed $value): bool
    {
        return is_numeric($value)
            && (int) $value >= 1
            && (int) $value <= 500;
    }

    public static function sanitizeSortKey(mixed $value): string
    {
        $value = sanitize_key((string) $value);
        return $value !== '' ? $value : self::DEFAULT_SORT;
    }

    public static function sanitizeBoolean(mixed $value): bool
    {
        return rest_sanitize_boolean($value);
    }

    public static function sanitizeCsvList(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        $items = array_map(
            static fn($item) => sanitize_key(trim((string) $item)),
            $items
        );

        return array_values(array_filter($items));
    }
}
