<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = DB::table('monitoring_psv')
    ->join('cois','cois.id','=','monitoring_psv.coi_id')
    ->join('tag_numbers','tag_numbers.id','=','cois.tag_number_id')
    ->whereRaw("SUBSTRING_INDEX(tag_numbers.tag_number,'/',1) IN ('4-PSV-171','4-PSV-172','4-PSV-173','4-PSV-176','4-PSV-177','4-PSV-178','4-PSV-184')")
    ->pluck('monitoring_psv.id');

if ($ids->count() > 0) {
    DB::table('monitoring_psv')->whereIn('id', $ids)->delete();
    echo "Deleted " . $ids->count() . " rows\n";
} else {
    echo "No rows to delete\n";
}
