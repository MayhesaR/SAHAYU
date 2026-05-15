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
        string $dateColumn = 'created_at',
        string $pageName = 'page',
        string $prefix = '',
    ): LengthAwarePaginator {
        // Apply prefix to all input names
        $p = $prefix ? "{$prefix}_" : "";

        // ═══ 1. SEARCH (OR LIKE across multiple columns) ═══
        $search = trim($request->input($p . 'search', ''));

        if ($search !== '' && count($searchableColumns) > 0) {
            $query->where(function (Builder $q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $field = array_pop($segments);
                        $relation = implode('.', $segments);
                        $q->orWhereHas($relation, function (Builder $relQuery) use ($field, $search) {
                            $relQuery->where($field, 'LIKE', "%{$search}%");
                        });
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // ═══ 2. EXACT-MATCH FILTERS ═══
        foreach ($filterableColumns as $column) {
            $inputKey = $p . $column; // Prefix the filter name too
            $value = $request->input($inputKey);

            if ($value !== null && $value !== '') {
                if (str_contains($column, '.')) {
                    $segments = explode('.', $column);
                    $field = array_pop($segments);
                    $relation = implode('.', $segments);
                    $query->whereHas($relation, function (Builder $relQuery) use ($field, $value) {
                        $relQuery->where($field, $value);
                    });
                } else {
                    $query->where($column, $value);
                }
            }
        }

        // ═══ 2.5 DATE RANGE FILTERS ═══
        $startDate = $request->input($p . 'start_date');
        $endDate = $request->input($p . 'end_date');

        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        // ═══ 3. SORT ═══
        $sortParam = $request->input($p . 'sort', '');

        if ($sortParam !== '') {
            if (preg_match('/^(.+)_(asc|desc)$/i', $sortParam, $matches)) {
                $sortColumn = $matches[1];
                $sortDirection = strtolower($matches[2]);

                $table = (new static)->getTable();
                $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

                if (in_array($sortColumn, $tableColumns, true)) {
                    $query->orderBy($sortColumn, $sortDirection);
                    if ($sortColumn !== 'id') {
                        $query->orderBy('id', 'desc');
                    }
                } else {
                    $query->orderBy($defaultSort, $defaultOrder)->orderBy('id', 'desc');
                }
            } else {
                $query->orderBy($defaultSort, $defaultOrder)->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy($defaultSort, $defaultOrder)->orderBy('id', 'desc');
        }

        // ═══ 4. PAGINATE ═══
        $perPageInput = (int) $request->input($p . 'per_page', $perPage);
        $perPageInput = min(max($perPageInput, 5), 100);

        return $query->paginate($perPageInput, ['*'], $pageName)->withQueryString();
    }
}
