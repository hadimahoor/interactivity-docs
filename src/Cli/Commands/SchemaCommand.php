<?php

declare(strict_types=1);

namespace InteractivityDocs\Cli\Commands;

use InteractivityDocs\Database\SchemaManager;
use WP_CLI;

defined('ABSPATH') || exit;

/**
 * Manage the plugin's custom database schema.
 *
 * ## EXAMPLES
 *
 *     # Create all plugin tables
 *     $ wp docs schema create
 *
 *     # Show current schema status
 *     $ wp docs schema status
 *
 *     # Drop all plugin tables (destructive)
 *     $ wp docs schema drop
 *
 *     # Drop and recreate all tables
 *     $ wp docs schema reset
 */
class SchemaCommand
{
    private SchemaManager $schemaManager;

    public function __construct()
    {
        $this->schemaManager = new SchemaManager();
    }

    /**
     * Create all plugin database tables.
     *
     * Runs dbDelta() for every table. Safe to run repeatedly; existing
     * tables are updated to match the current schema definition.
     *
     * ## OPTIONS
     *
     * [--force]
     * : Recreate tables even if they already exist (drops first).
     *
     * @when after_wp_load
     */
    public function create($args, $assoc_args): void
    {
        $force = isset($assoc_args['force']);

        if ($force) {
            WP_CLI::warning('Force mode: existing tables will be dropped first.');
            WP_CLI::confirm('This will delete all data in plugin tables. Continue?', $assoc_args);
            $this->schemaManager->dropTables();
        }

        $this->schemaManager->createTables();

        WP_CLI::success('Schema created/updated successfully.');
        $this->renderStatusTable();
    }

    /**
     * Show status of all plugin tables (existence and row counts).
     *
     * @when after_wp_load
     */
    public function status($args, $assoc_args): void
    {
        $this->renderStatusTable();
    }

    /**
     * Drop all plugin database tables.
     *
     * Destructive: removes all data stored in the custom tables.
     * WordPress core tables (posts, terms, etc.) are not affected.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip the confirmation prompt.
     *
     * @when after_wp_load
     */
    public function drop($args, $assoc_args): void
    {
        WP_CLI::warning('This will permanently delete all plugin tables and their data.');
        WP_CLI::confirm('Are you sure you want to drop all plugin tables?', $assoc_args);

        $this->schemaManager->dropTables();

        WP_CLI::success('All plugin tables dropped.');
    }

    /**
     * Drop and recreate all plugin tables.
     *
     * Equivalent to `drop` followed by `create`. All existing data
     * in the custom tables is lost.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip the confirmation prompt.
     *
     * @when after_wp_load
     */
    public function reset($args, $assoc_args): void
    {
        WP_CLI::warning('This will drop and recreate all plugin tables. All data will be lost.');
        WP_CLI::confirm('Continue with schema reset?', $assoc_args);

        $this->schemaManager->dropTables();
        $this->schemaManager->createTables();

        WP_CLI::success('Schema reset complete.');
        $this->renderStatusTable();
    }

    /**
     * Render a formatted table of the current schema status.
     */
    private function renderStatusTable(): void
    {
        $status = $this->schemaManager->getStatus();

        $rows = array_map(
            static fn(array $item): array => [
                'table'  => $item['table'],
                'status' => $item['exists'] ? 'exists' : 'missing',
                'rows'   => $item['exists'] ? (string) $item['rows'] : '-',
            ],
            $status
        );

        WP_CLI\Utils\format_items('table', $rows, ['table', 'status', 'rows']);
    }
}
