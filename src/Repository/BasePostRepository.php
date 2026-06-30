<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

use InteractivityDocs\Models\BaseEntity;

defined('ABSPATH') || exit;

/**
 * BasePostRepository
 *
 * The generic insert() and update() methods are locked in this class to ensure
 * all save operations go through each repository's dedicated upsertMain().
 *
 * @package InteractivityDocs\Repository
 * @since 1.0.0
 */
abstract class BasePostRepository extends BaseRepository implements PostRepositoryInterface
{
    protected string $modelClass;

    public function __construct(\wpdb $db, string $table, string $idColumn, string $modelClass)
    {
        parent::__construct($db, $table, $idColumn);
        $this->modelClass = $modelClass;
    }

    abstract public function getPostType(): string;

    /**
     * Convert a database row into a model.
     *
     * Maps the table's primary key (e.g. person_id / paper_id / book_id) to 'id'
     * so BaseEntity::getId() is populated correctly and the id key is not dropped in toArray().
     */
    protected function hydrate(array $row): BaseEntity
    {
        if (!isset($row['id']) && isset($row[$this->idColumn])) {
            $row['id'] = $row[$this->idColumn];
        }

        return new $this->modelClass($row);
    }

    public function find(int $id): ?BaseEntity
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->idColumn} = %d", $id), ARRAY_A);
        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(array $criteria = []): array
    {
        $results = $this->db->get_results("SELECT * FROM {$this->table}", ARRAY_A);
        return array_map(fn($row) => $this->hydrate($row), $results ?: []);
    }

    /**
     * @throws \LogicException Using this method is forbidden for post repositories.
     */
    final public function insert(BaseEntity $entity): bool
    {
        throw new \LogicException('Use upsertMain() instead of insert() for post repositories.');
    }

    /**
     * @throws \LogicException Using this method is forbidden for post repositories.
     */
    final public function update(BaseEntity $entity): bool
    {
        throw new \LogicException('Use upsertMain() instead of update() for post repositories.');
    }

    public function exists(int $post_id): bool
    {
        $count = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$this->idColumn} = %d", $post_id));
        return (int)$count > 0;
    }

    public function deleteByPostId(int $post_id): bool
    {
        // Uses $wpdb's own delete() method, available via the parent class.
        return (bool) $this->db->delete($this->table, [$this->idColumn => $post_id], ['%d']);
    }

    public function fetchForSync(int $post_id): ?BaseEntity
    {
        return $this->find($post_id);
    }

    /**
     * Columns allowed to be filtered (facets).
     *
     * Overridden in children so only columns relevant to that entity are allowed.
     */
    protected function allowedFilterColumns(): array
    {
        // Default: no filters allowed.
        return [];
    }

    /**
     * Map of sortKey => [column, direction].
     *
     * This could later be wired to a SortConfig; for now it is kept in the repository,
     * or the Controller resolves it before calling and passes it directly.
     */
    protected function allowedSortColumns(): array
    {
        return [
            'created_at',
            'title',
            'view_count',
            'like_count',
            'year',
            'paper_count',
            'book_count',
        ];
    }

    /**
     * Where clause builder.
     *
     * @param array $filters Example: ['year' => '1402', 'language' => 'fa']
     * @param string|null $status Example: 'publish' (default)
     * @return array{sql:string, params:array}
     */
    protected function buildWhere(array $filters, ?string $status = 'publish'): array
    {
        $clauses = ['1=1'];
        $params  = [];
        if ($status !== null && $status !== '') {
            $clauses[] = "post_status = %s";
            $params[]  = $status;
        }

        $allowed = array_flip($this->allowedFilterColumns());
        foreach ($filters as $key => $value) {
            if (!isset($allowed[$key])) {
                continue;
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            // Array support => IN (...)
            if (is_array($value)) {
                $value = array_values(array_filter(array_map('strval', $value), static fn($v) => $v !== ''));
                if ($value === []) {
                    continue;
                }

                $placeholders = implode(',', array_fill(0, count($value), '%s'));
                $clauses[] = "`{$key}` IN ({$placeholders})";
                foreach ($value as $v) {
                    $params[] = $v;
                }
                continue;
            }

            $clauses[] = "`{$key}` = %s";
            $params[]  = (string) $value;
        }

        $sql = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Get a paginated list.
     *
     * @param array{
     *   page?:int,
     *   per_page?:int,
     *   filters?:array,
     *   status?:string|null,
     *   sort_by?:string,
     *   sort_dir?:string
     * } $args
     *
     * @return array{
     *   items: array[],
     *   page:int,
     *   per_page:int
     * }
     */
    public function findPaginated(array $args): array
    {
        $page    = max(1, (int)($args['page'] ?? 1));
        $perPage = (int)($args['per_page'] ?? 12);
        // Cap to prevent abuse.
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;
        $filters = (array)($args['filters'] ?? []);
        $status  = array_key_exists('status', $args) ? $args['status'] : 'publish';
        $sortBy  = (string)($args['sort_by'] ?? 'created_at');
        $sortDir = strtoupper((string)($args['sort_dir'] ?? 'DESC'));
        $sortDir = in_array($sortDir, ['ASC', 'DESC'], true) ? $sortDir : 'DESC';

        // Prevent SQL injection in ORDER BY (column must be whitelisted).
        if (!in_array($sortBy, $this->allowedSortColumns(), true)) {
            $sortBy = 'created_at';
        }

        $where = $this->buildWhere($filters, $status);

        // LIMIT/OFFSET are inlined as ints (safe because they are cast).
        $sql = "
            SELECT *
            FROM {$this->table}
            {$where['sql']}
            ORDER BY `{$sortBy}` {$sortDir}
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $prepared = $where['params']
            ? $this->db->prepare($sql, $where['params'])
            : $sql;
        $rows = $this->db->get_results($prepared, ARRAY_A) ?: [];
        $items = array_map(function ($row) {
            // 1. Convert the DB row into a model (mapping idColumn to id) and then to an array.
            $item = $this->hydrate($row)->toArray();

            // 2. If a 'data' field exists and is a JSON string.
            if (isset($item['data']) && is_string($item['data'])) {
                $decoded = json_decode($item['data'], true);
                if (is_array($decoded)) {
                    // 3. Important: merge the data contents into item and drop the previous string field.
                    $item = array_merge($item, $decoded);
                    unset($item['data']);
                }
            }

            return $item;
        }, $rows);
        return [
            'items'    => $items,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Total record count with filters.
     */
    public function countByFilters(array $filters, ?string $status = 'publish'): int
    {
        $where = $this->buildWhere($filters, $status);
        $sql = "
            SELECT COUNT(*)
            FROM {$this->table}
            {$where['sql']}
        ";
        $prepared = $where['params']
            ? $this->db->prepare($sql, $where['params'])
            : $sql;
        return (int) $this->db->get_var($prepared);
    }

    /**
     * Build meta (facets) with GROUP BY.
     *
     * Example output:
     * [
     *   'year' => [
     *      ['value' => '1402', 'count' => 123],
     *      ...
     *   ],
     *   'language' => ...
     * ]
     *
     * @param array $filters Currently applied filters.
     * @param string|null $status
     * @param array|null $only Build meta only for these facets (optional).
     */
    public function getFacetedMeta(array $filters, ?string $status = 'publish', ?array $only = null): array
    {
        $allowedFacets = $this->allowedFilterColumns();
        if (!$allowedFacets) {
            return [];
        }

        // Keep only valid filters.
        $allowedFlip = array_flip($allowedFacets);
        $validFilters = [];
        foreach ($filters as $key => $value) {
            if (!isset($allowedFlip[$key])) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $validFilters[$key] = $value;
        }

        // 2. Decide which columns should have meta built.
        // We no longer diff between all filters and the selected ones.
        $facetsToBuild = $allowedFacets;

        // If 'only' was provided, narrow the output.
        if ($only) {
            $onlyFlip = array_flip($only);
            $facetsToBuild = array_values(array_filter($facetsToBuild, static fn(string $facet): bool => isset($onlyFlip[$facet])));
        }

        if (!$facetsToBuild) {
            return [];
        }

        $where = $this->buildWhere($validFilters, $status);
        $meta = [];
        foreach ($facetsToBuild as $facetColumn) {
            // Extra guard (even if redundant in the normal flow).
            if (!isset($allowedFlip[$facetColumn])) {
                continue;
            }

            $sql = "
                SELECT `{$facetColumn}` AS value, COUNT(*) AS count
                FROM {$this->table}
                {$where['sql']}
                  AND `{$facetColumn}` IS NOT NULL
                  AND `{$facetColumn}` <> ''
                GROUP BY `{$facetColumn}`
                ORDER BY count DESC
                LIMIT 200
            ";
            $prepared = $where['params']
                ? $this->db->prepare($sql, $where['params'])
                : $sql;
            $rows = $this->db->get_results($prepared, ARRAY_A) ?: [];
            $meta[$facetColumn] = array_map(static fn(array $row): array => [
                    'value' => (string) $row['value'],
                    'count' => (int) $row['count'],
                ], $rows);
        }

        return $meta;
    }

    /**
     * Get all unique values of an allowed column for use in the block editor.
     *
     * @param string $column
     * @param string|null $status
     * @param int $limit
     * @return array<int, string>
     */
    public function getUniqueValues(string $column, ?string $status = 'publish', int $limit = 200): array
    {
        $column = sanitize_key($column);
        $allowed = $this->allowedFilterColumns();
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid facet column: {$column}");
        }

        $limit = max(1, min(500, (int) $limit));
        $clauses = ['1=1'];
        $params = [];
        if ($status !== null && $status !== '') {
            $clauses[] = 'post_status = %s';
            $params[] = $status;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $clauses);
        $sql = "
            SELECT DISTINCT `{$column}` AS value
            FROM {$this->table}
            {$whereSql}
            AND `{$column}` IS NOT NULL
            AND `{$column}` <> ''
            ORDER BY `{$column}` ASC
            LIMIT {$limit}
        ";

        $prepared = $params
            ? $this->db->prepare($sql, $params)
            : $sql;
        $values = $this->db->get_col($prepared) ?: [];
        return array_values(array_filter(array_map(static fn($value): string => trim((string) $value), $values), static fn(string $value): bool => $value !== ''));
    }

    /**
     * Get all filter options for the block editor.
     *
     * Example output:
     * [
     *   'year' => [
     *     ['label' => '1402', 'value' => '1402'],
     *     ['label' => '1401', 'value' => '1401'],
     *   ],
     *   'language' => [
     *     ['label' => 'fa', 'value' => 'fa'],
     *     ['label' => 'en', 'value' => 'en'],
     *   ],
     * ]
     *
     * @param string|null $status
     * @param int $limitPerFacet
     * @return array<string, array<int, array{label:string,value:string}>>
     */
    public function getAllFacetOptions(?string $status = 'publish', int $limitPerFacet = 200): array
    {
        $result = [];
        foreach ($this->allowedFilterColumns() as $column) {
            $values = $this->getUniqueValues($column, $status, $limitPerFacet);
            $result[$column] = array_map(static fn(string $value): array => [
                    'label' => $value,
                    'value' => $value,
                ], $values);
        }

        return $result;
    }
}
