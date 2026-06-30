<?php

declare(strict_types=1);

namespace InteractivityDocs\Support;

defined('ABSPATH') || exit;

/**
 * UI utility functions for the docs archive block.
 */
class UIHelpers
{
    /**
     * Ellipsis marker used to collapse skipped page ranges.
     */
    private const ELLIPSIS = '…';

    /**
     * Build metadata counts from item taxonomies.
     *
     * Each item carries its taxonomy terms under the `data` key, where every
     * taxonomy (year, language, paper_type, magazine) is a single term object
     * shaped as `['name' => ..., 'url' => ...]`.
     *
     * @param array $items List of items, each holding a 'data' key with taxonomy terms.
     * @return array Nested array shaped as [taxonomy => [term name => count]].
     */
    public static function buildCurrentMeta(array $items): array
    {
        $currentMeta = [];
        $taxonomies  = ['year', 'language', 'paper_type', 'magazine'];

        foreach ($items as $item) {
            $data = $item['data'] ?? $item;

            foreach ($taxonomies as $tax) {
                $name = $data[$tax]['name'] ?? null;

                if ($name === null) {
                    continue;
                }

                $currentMeta[$tax][$name] = ($currentMeta[$tax][$name] ?? 0) + 1;
            }
        }

        return $currentMeta;
    }


    /**
     * Generate a windowed pagination structure with ellipsis logic.
     *
     * With a 2-page radius around the current page the pattern is:
     *
     *     [1] … [current-2 .. current+2] … [max]
     *
     * Leading/trailing ellipses appear only when there is an actual gap
     * between the edges and the visible window.
     *
     * @param array $params Must contain 'current' (int) and 'max' (int).
     * @return array|null Pagination data, or null when params are invalid.
     */
    public static function paginate(array $params): ?array
    {
        $current = $params['current'] ?? null;
        $max     = $params['max'] ?? null;

        if (!is_int($current) || !is_int($max) || $current < 1 || $max < 1) {
            return null;
        }

        $prev = $current === 1 ? false : $current - 1;
        $next = $current === $max ? false : $current + 1;

        // First page is always present.
        $items = [
            ['item' => 1, 'isCurrent' => $current === 1],
        ];

        // Single-page case: nothing more to build.
        if ($max === 1) {
            return [
                'current' => $current,
                'prev'    => $prev,
                'next'    => $next,
                'items'   => $items,
            ];
        }

        // Window boundaries around the current page.
        $radius = 2;
        $start  = max(2, $current - $radius);
        $end    = min($max, $current + $radius);

        // Leading ellipsis when there's a gap between page 1 and the window.
        if ($start > 2) {
            $items[] = ['item' => self::ELLIPSIS, 'isCurrent' => false];
        }

        // Inner window.
        for ($i = $start; $i <= $end; $i++) {
            $items[] = ['item' => $i, 'isCurrent' => $i === $current];
        }

        // Trailing ellipsis when there's a gap between the window and the last page.
        if ($end < $max - 1) {
            $items[] = ['item' => self::ELLIPSIS, 'isCurrent' => false];
        }

        // Last page, when not already covered by the window.
        if ($end < $max) {
            $items[] = ['item' => $max, 'isCurrent' => $max === $current];
        }

        return [
            'current' => $current,
            'prev'    => $prev,
            'next'    => $next,
            'items'   => $items,
        ];
    }
}
