<?php

declare(strict_types=1);

namespace InteractivityDocs\Cli\Commands;

use WP_CLI;
use InteractivityDocs\Database\TableNames;

defined('ABSPATH') || exit;

/**
 * Verifies data integrity for InteractivityDocs plugin.
 *
 * ## EXAMPLES
 *
 *     wp docs verify all
 *     wp docs verify counts
 *     wp docs verify counts --fix
 *     wp docs verify relations
 *     wp docs verify schema
 *
 * @package InteractivityDocs\Cli\Commands
 */
class VerifyCommand
{
    /**
     * Run all integrity checks.
     *
     * ## EXAMPLES
     *
     *     wp docs verify all
     *
     * @subcommand all
     */
    public function all(array $args, array $assocArgs): void
    {
        WP_CLI::log('Running all integrity checks...');
        WP_CLI::log('');

        $this->schema($args, $assocArgs);
        WP_CLI::log('');

        $this->counts($args, $assocArgs);
        WP_CLI::log('');

        $this->relations($args, $assocArgs);
    }


    /**
     * Check paper_count and book_count consistency for all persons.
     *
     * Compares stored counts in the person table against
     * actual row counts in the paper_person and book_person tables.
     *
     * ## OPTIONS
     *
     * [--fix]
     * : Automatically fix count mismatches by recalculating from relation tables.
     *
     * ## EXAMPLES
     *
     *     wp docs verify counts
     *     wp docs verify counts --fix
     *
     */
    public function counts(array $args, array $assocArgs): void
    {
        global $wpdb;

        $fix = \WP_CLI\Utils\get_flag_value($assocArgs, 'fix', false);

        WP_CLI::log('Checking person counts...');

        $personTable      = TableNames::person();
        $paperPersonTable = TableNames::paperPerson();
        $bookPersonTable  = TableNames::bookPerson();

        $rows = $wpdb->get_results(
            "SELECT
                p.person_id,
                p.title,
                p.paper_count        AS stored_paper_count,
                p.book_count         AS stored_book_count,
                COALESCE(pp.total,0) AS actual_paper_count,
                COALESCE(bp.total,0) AS actual_book_count
            FROM {$personTable} p
            LEFT JOIN (
                SELECT person_id, COUNT(*) AS total
                FROM {$paperPersonTable}
                GROUP BY person_id
            ) pp ON p.person_id = pp.person_id
            LEFT JOIN (
                SELECT person_id, COUNT(*) AS total
                FROM {$bookPersonTable}
                GROUP BY person_id
            ) bp ON p.person_id = bp.person_id
            WHERE
                p.paper_count != COALESCE(pp.total, 0)
                OR p.book_count  != COALESCE(bp.total, 0)",
            ARRAY_A
        );

        if (empty($rows)) {
            WP_CLI::success('All person counts are correct.');
            return;
        }

        $items = array_map(fn($r) => [
            'person_id'     => $r['person_id'],
            'title'         => $r['title'],
            'stored_papers' => $r['stored_paper_count'],
            'actual_papers' => $r['actual_paper_count'],
            'stored_books'  => $r['stored_book_count'],
            'actual_books'  => $r['actual_book_count'],
        ], $rows);

        \WP_CLI\Utils\format_items(
            'table',
            $items,
            ['person_id', 'title', 'stored_papers', 'actual_papers', 'stored_books', 'actual_books']
        );

        WP_CLI::warning(sprintf('%d person(s) have mismatched counts.', count($rows)));

        if ($fix) {
            WP_CLI::log('Fixing count mismatches...');

            $personIds = array_map('intval', array_column($rows, 'person_id'));
            $this->recalculate($wpdb, $personIds, 'paper');
            $this->recalculate($wpdb, $personIds, 'book');

            WP_CLI::success(sprintf('Fixed counts for %d person(s).', count($personIds)));
        } else {
            WP_CLI::log('Run with --fix to automatically correct these mismatches.');
        }
    }

    /**
     * Check for orphaned records in relation (junction) tables.
     *
     * Detects rows in paper_person / book_person whose parent post
     * or person no longer exists in the entity tables.
     *
     * ## EXAMPLES
     *
     *     wp docs verify relations
     *
     */
    public function relations(array $args, array $assocArgs): void
    {
        global $wpdb;

        WP_CLI::log('Checking relation tables for orphaned records...');

        $pp  = TableNames::paperPerson();
        $bp  = TableNames::bookPerson();
        $p   = TableNames::paper();
        $b   = TableNames::book();
        $per = TableNames::person();

        $checks = [
            [
                'label'   => 'paper_person → missing paper',
                'query'   => "
                    SELECT pp.paper_id AS orphan_id, pp.person_id
                    FROM {$pp} pp
                    LEFT JOIN {$p} p ON pp.paper_id = p.paper_id
                    WHERE p.paper_id IS NULL
                ",
                'columns' => ['orphan_id', 'person_id'],
            ],
            [
                'label'   => 'paper_person → missing person',
                'query'   => "
                    SELECT pp.paper_id, pp.person_id AS orphan_id
                    FROM {$pp} pp
                    LEFT JOIN {$per} per ON pp.person_id = per.person_id
                    WHERE per.person_id IS NULL
                ",
                'columns' => ['paper_id', 'orphan_id'],
            ],
            [
                'label'   => 'book_person → missing book',
                'query'   => "
                    SELECT bp.book_id AS orphan_id, bp.person_id
                    FROM {$bp} bp
                    LEFT JOIN {$b} b ON bp.book_id = b.book_id
                    WHERE b.book_id IS NULL
                ",
                'columns' => ['orphan_id', 'person_id'],
            ],
            [
                'label'   => 'book_person → missing person',
                'query'   => "
                    SELECT bp.book_id, bp.person_id AS orphan_id
                    FROM {$bp} bp
                    LEFT JOIN {$per} per ON bp.person_id = per.person_id
                    WHERE per.person_id IS NULL
                ",
                'columns' => ['book_id', 'orphan_id'],
            ],
        ];

        $totalIssues = 0;

        foreach ($checks as $check) {
            $rows  = $wpdb->get_results($check['query'], ARRAY_A);
            $count = count($rows);
            $totalIssues += $count;

            if ($count === 0) {
                WP_CLI::log(sprintf('  ✓ %s', $check['label']));
            } else {
                WP_CLI::warning(sprintf('  ✗ %s — %d orphaned row(s)', $check['label'], $count));
                \WP_CLI\Utils\format_items('table', $rows, $check['columns']);
            }
        }

        if ($totalIssues === 0) {
            WP_CLI::success('No orphaned relations found.');
        } else {
            WP_CLI::warning(sprintf('Total: %d orphaned relation row(s) found.', $totalIssues));
        }
    }


    /**
     * Check that all expected plugin tables exist in the database.
     *
     * ## EXAMPLES
     *
     *     wp docs verify schema
     *
     */
    public function schema(array $args, array $assocArgs): void
    {
        global $wpdb;

        WP_CLI::log('Checking plugin tables...');

        $expected = [
            'person'       => TableNames::person(),
            'paper'        => TableNames::paper(),
            'book'         => TableNames::book(),
            'paper_person' => TableNames::paperPerson(),
            'book_person'  => TableNames::bookPerson(),
        ];

        $items        = [];
        $missingCount = 0;

        foreach ($expected as $name => $fullName) {
            $exists = $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $fullName)
            ) === $fullName;

            if (!$exists) {
                $missingCount++;
            }

            $items[] = [
                'table'     => $name,
                'full_name' => $fullName,
                'status'    => $exists ? 'OK' : 'MISSING',
            ];
        }

        \WP_CLI\Utils\format_items('table', $items, ['table', 'full_name', 'status']);

        if ($missingCount === 0) {
            WP_CLI::success('All plugin tables are present.');
        } else {
            WP_CLI::error(
                sprintf('%d table(s) missing. Run: wp docs schema create', $missingCount),
                false  // don't halt execution when called from all()
            );
        }
    }

    //-------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recalculate paper_count or book_count for the given person IDs.
     *
     * Mirrors PersonRepository::recalculateCounts() so VerifyCommand --fix
     * stays self-contained and doesn't depend on the repository layer.
     */
    private function recalculate(\wpdb $wpdb, array $personIds, string $postType): void
    {
        if (empty($personIds)) {
            return;
        }

        $personTable   = $wpdb->prefix . 'person';
        $relationTable = $wpdb->prefix . $postType . '_person';
        $countColumn   = $postType . '_count';
        $postColumn    = $postType . '_id';

        $placeholder = implode(',', array_map('intval', $personIds));

        $wpdb->query("
            UPDATE {$personTable} p
            LEFT JOIN (
                SELECT person_id, COUNT(*) AS total
                FROM {$relationTable}
                WHERE {$postColumn} IS NOT NULL
                GROUP BY person_id
            ) rel ON p.person_id = rel.person_id
            SET p.{$countColumn} = COALESCE(rel.total, 0)
            WHERE p.person_id IN ({$placeholder})
        ");
    }
}
