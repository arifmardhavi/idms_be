<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function normalize($date)
    {
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}