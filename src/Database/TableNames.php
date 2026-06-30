<?php

declare(strict_types=1);

namespace InteractivityDocs\Database;

defined('ABSPATH') || exit;

/**
 * Table Names Manager
 *
 * Centralized management of custom database table names.
 * All table names are prefixed with the WordPress database prefix
 * and cached for performance.
 *
 * @package InteractivityDocs\Database
 * @since 1.0.0
 */
final class TableNames
{
    /**
     * Cache for table names to avoid repeated prefix lookups
     *
     * @var array<string, string>
     */
    private static array $cache = [];

    /**
     * Get the paper table name
     *
     * @return string Full table name with WordPress prefix
     */
    public static function paper(): string
    {
        return self::getTableName('paper');
    }

    /**
     * Get the book table name
     *
     * @return string Full table name with WordPress prefix
     */
    public static function book(): string
    {
        return self::getTableName('book');
    }

    /**
     * Get the person table name
     *
     * @return string Full table name with WordPress prefix
     */
    public static function person(): string
    {
        return self::getTableName('person');
    }

    /**
     * Get the paper-person relationship table name
     *
     * @return string Full table name with WordPress prefix
     */
    public static function paperPerson(): string
    {
        return self::getTableName('paper_person');
    }

    /**
     * Get the book-person relationship table name
     *
     * @return string Full table name with WordPress prefix
     */
    public static function bookPerson(): string
    {
        return self::getTableName('book_person');
    }

    /**
     * Build and cache table name with WordPress prefix
     *
     * @param string $suffix Table name suffix (without prefix)
     * @return string Full table name with WordPress prefix
     */
    private static function getTableName(string $suffix): string
    {
        if (!isset(self::$cache[$suffix])) {
            global $wpdb;
            self::$cache[$suffix] = $wpdb->prefix . $suffix;
        }
        return self::$cache[$suffix];
    }
}
