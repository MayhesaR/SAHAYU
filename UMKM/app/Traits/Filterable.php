<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Trait Filterable
 *
 * Provides a reusable scope for Search, Filter, Sort, and Pagination.
 * Usage on any Model: `use App\Traits\Filterable;`
 * Then call: `Model::filterSortPaginate($request, [...searchable columns...])`
 *
 * This replaces repetitive `when()` chains across controllers with a single,
 * configurable scope call.
 */
trait Filterable
{
    /**
     * Scope: Search, Filter, Sort, and Paginate in one call.
     *
     * @param  Builder     $query             The Eloquent query builder instance (auto-injected by Laravel scopes).
     * @param  Request     $request           The current HTTP request (to read query params: search, sort, filters).
     * @param  array       $searchableColumns Columns to search with OR LIKE when ?search= is present.
     *                                        Supports dot-notation for relations, e.g. 'product.name'.
     * @param  array       $filterableColumns Columns that accept exact-match filters from query params.
     *                                        e.g. ['status', 'category'] allows ?status=done&category=Dasar.
     * @param  string      $defaultSort       Default column to sort by if no ?sort= param is given.
     * @param  string      $defaultOrder      Default sort direction ('asc' or 'desc').
     * @param  int         $perPage           Number of results per page.
     * @return LengthAwarePaginator
     */
    public function scopeFilterSortPaginate(
        Builder $query,
        Request $request,
        array $searchableColumns = [],
        array $filterableColumns = [],
        string $defaultSort = 'created_at',
        string $defaultOrder = 'desc',
        int $perPage = 10,
    ): LengthAwarePaginator {
        // ═══ 1. SEARCH (OR LIKE across multiple columns) ═══
        $search = trim($request->input('search', ''));

        if ($search !== '' && count($searchableColumns) > 0) {
            $query->where(function (Builder $q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    // Support dot-notation for relationship searches
                    // Single level: 'product.name' -> whereHas('product', ...)
                    // Multi level:  'items.product.name' -> whereHas('items.product', ...)
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $field = array_pop($segments);          // Last segment = column name
                        $relation = implode('.', $segments);    // Rest = relation path
                        $q->orWhereHas($relation, function (Builder $relQuery) use ($field, $search) {
                            $relQuery->where($field, 'LIKE', "%{$search}%");
                        });
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // ═══ 2. EXACT-MATCH FILTERS (e.g. ?status=done&category=Dasar) ═══
        foreach ($filterableColumns as $column) {
            $value = $request->input($column);

            if ($value !== null && $value !== '') {
                // Support dot-notation for relationship filters
                if (str_contains($column, '.')) {
                    [$relation, $field] = explode('.', $column, 2);
                    $query->whereHas($relation, function (Builder $relQuery) use ($field, $value) {
                        $relQuery->where($field, $value);
                    });
                } else {
                    $query->where($column, $value);
                }
            }
        }

        // ═══ 3. SORT (parse 'column_asc' or 'column_desc' from ?sort=) ═══
        $sortParam = $request->input('sort', '');

        if ($sortParam !== '') {
            // Try to parse the sort parameter: last segment is direction, rest is column name
            // Examples: 'name_asc', 'created_at_desc', 'production_date_asc'
            if (preg_match('/^(.+)_(asc|desc)$/i', $sortParam, $matches)) {
                $sortColumn = $matches[1];
                $sortDirection = strtolower($matches[2]);

                // Security: only allow sorting by actual table columns (prevent SQL injection)
                $table = (new static)->getTable();
                $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

                if (in_array($sortColumn, $tableColumns, true)) {
                    $query->orderBy($sortColumn, $sortDirection);
                } else {
                    // Fallback to default if column doesn't exist
                    $query->orderBy($defaultSort, $defaultOrder);
                }
            } else {
                $query->orderBy($defaultSort, $defaultOrder);
            }
        } else {
            $query->orderBy($defaultSort, $defaultOrder);
        }

        // ═══ 4. PAGINATE ═══
        $perPage = (int) $request->input('per_page', $perPage);
        $perPage = min(max($perPage, 5), 100); // Clamp between 5 and 100

        return $query->paginate($perPage)->withQueryString();
    }
}
