<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared query helpers for the admin data tables.
 *
 * Every admin index endpoint speaks the same wire format:
 *   in  -> page, per_page, search, sort_by, order_by, + per-resource filters
 *   out -> data: [...], meta: { current_page, per_page, total, last_page }
 *
 * The frontend `useServerTable` composable is built against exactly this contract.
 */
class TableQuery
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * Resolve a bounded page size. Never returns 0 or a negative value.
     */
    public static function perPage(Request $request, int $default = self::DEFAULT_PER_PAGE): int
    {
        $requested = $request->input('per_page');

        // a non-numeric per_page falls back to the default rather than clamping to 1
        $perPage = is_numeric($requested) ? (int) $requested : $default;

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }

    /**
     * The free-text search term, or null when absent/blank.
     */
    public static function term(Request $request): ?string
    {
        $term = trim((string) $request->input('search', ''));

        return $term === '' ? null : $term;
    }

    /**
     * Apply the free-text search across the given columns.
     *
     * The LIKEs are grouped, so a search never leaks past an active filter the
     * way a bare chain of orWhere() would.
     *
     * @param  array<int, string>  $columns
     */
    public static function search(Builder $query, Request $request, array $columns): Builder
    {
        $term = self::term($request);

        if ($term === null || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($columns, $term): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    /**
     * Apply exact-match filters for whichever of the given keys the request carries.
     *
     * Pass 'key' when the query param and the column share a name, or
     * 'param' => 'column' when they differ.
     *
     * @param  array<int|string, string>  $filters
     */
    public static function filters(Builder $query, Request $request, array $filters): Builder
    {
        foreach ($filters as $param => $column) {
            if (is_int($param)) {
                $param = $column;
            }

            if (! $request->filled($param)) {
                continue;
            }

            $value = $request->input($param);
            // a list (type[]=A&type[]=B, or type=A,B) narrows to any of them
            $values = is_array($value) ? $value : (str_contains((string) $value, ',') ? explode(',', (string) $value) : null);

            if ($values !== null) {
                $query->whereIn($column, array_values(array_filter(array_map('trim', $values), fn ($v) => $v !== '')));
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Apply a whitelisted ORDER BY.
     *
     * Anything outside $allowed falls back to the resource's natural order, so a
     * crafted `sort_by` can never reach the SQL grammar.
     *
     * @param  array<int, string>  $allowed
     */
    public static function sort(
        Builder $query,
        Request $request,
        array $allowed,
        string $default,
        string $defaultDirection = 'desc'
    ): Builder {
        $column = (string) ($request->input('sort_by') ?? $request->input('sortBy') ?? '');
        $direction = strtolower((string) ($request->input('order_by') ?? $request->input('orderBy') ?? ''));

        if (! in_array($column, $allowed, true)) {
            return $query->orderBy($default, $defaultDirection);
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return $query->orderBy($column, $direction);
    }

    /**
     * The pagination envelope the data table reads.
     *
     * @return array<string, int>
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
