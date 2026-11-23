<?php

namespace App\Support;

use App\Models\Tag_number;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu-satunya definisi aturan kolom tag_number_ids.
 *
 * Format tersimpan: "43,44,93" atau literal "all". Tidak ada kolom boolean
 * terpisah. $column pada method SQL selalu konstanta internal controller,
 * bukan input user.
 */
class TagNumberIds
{
    public const MARKER = 'all';

    /** Cache statis per request: id => tag_number. */
    private static array $cache = [];

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (strtolower($value) === self::MARKER) {
            return self::MARKER;
        }

        $parts = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $value)),
            fn ($part) => $part !== ''
        )));

        return $parts === [] ? null : implode(',', $parts);
    }

    public static function isAll(?string $value): bool
    {
        return strtolower(trim((string) $value)) === self::MARKER;
    }

    /** @return array<int> */
    public static function parse(?string $value): array
    {
        if (self::isAll($value) || trim((string) $value) === '') {
            return [];
        }

        return array_map(
            'intval',
            explode(',', self::normalize($value) ?? '')
        );
    }

    /**
     * @return array<int, array{id:int, tag_number:string}>
     */
    public static function resolve(?string $value): array
    {
        $ids = self::parse($value);

        if ($ids === []) {
            return [];
        }

        $missing = array_filter(
            $ids,
            fn ($id) => ! array_key_exists($id, self::$cache)
        );

        if ($missing !== []) {
            foreach (Tag_number::whereIn('id', $missing)->get(['id', 'tag_number']) as $tag) {
                self::$cache[(int) $tag->id] = (string) $tag->tag_number;
            }
        }

        return array_map(fn ($id) => [
            'id' => $id,
            'tag_number' => self::$cache[$id] ?? (string) $id,
        ], $ids);
    }

    public static function display(?string $value): string
    {
        if (self::isAll($value)) {
            return 'All Tag Number';
        }

        return implode(', ', array_column(self::resolve($value), 'tag_number'));
    }

    public static function applyFilter(Builder $query, string $column, ?string $value): Builder
    {
        if ($value === null || trim($value) === '') {
            return $query;
        }

        if (self::isAll($value)) {
            return $query->where($column, self::MARKER);
        }

        $ids = self::parse($value);

        if ($ids === []) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($column, $ids) {
            foreach ($ids as $id) {
                $query->orWhereRaw('FIND_IN_SET(?, ' . $column . ') > 0', [$id]);
            }
        });
    }

    /**
     * Baris bertanda 'all' otomatis tidak cocok karena
     * FIND_IN_SET(id, 'all') selalu 0.
     */
    public static function tagSearchSql(string $column): string
    {
        return 'EXISTS (SELECT 1 FROM tag_numbers'
            . ' WHERE FIND_IN_SET(tag_numbers.id, ' . $column . ') > 0'
            . ' AND tag_numbers.tag_number LIKE ?)';
    }
}
