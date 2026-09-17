<?php

namespace App\Support;

class ActivityModule
{
    /**
     * Mengubah nilai `module` mentah menjadi label kanonikal FE.
     *
     * Urutan:
     *   1. cocok dengan config 'log-activity.aliases'   (identifier BE -> label FE)
     *   2. cocok dengan key config 'log-activity.features' (path FE -> label FE)
     *   3. sudah merupakan label FE (value config 'log-activity.features') -> idempotent
     *   4. prettify fallback (camelCase/snake_case -> Title Case)
     */
    public static function label(string $module): string
    {
        $value = trim($module);

        if ($value === '') {
            return '';
        }

        $aliases = config('log-activity.aliases', []);
        if (array_key_exists($value, $aliases)) {
            return $aliases[$value];
        }

        $features = config('log-activity.features', []);
        if (array_key_exists($value, $features)) {
            return $features[$value];
        }

        if (in_array($value, $features, true)) {
            return $value;
        }

        return self::prettify($value);
    }

    /**
     * camelCase / snake_case -> Title Case. Mis. "SpkNew" -> "Spk New",
     * "EcaFsca" -> "Eca Fsca", "666" -> "666".
     */
    protected static function prettify(string $value): string
    {
        $spaced = str_replace(['_', '-'], ' ', $value);
        $spaced = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $spaced);

        return ucwords(trim($spaced));
    }
}