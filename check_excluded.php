<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('monitoring_psv')
    ->join('cois','cois.id','=','monitoring_psv.coi_id')
    ->join('tag_numbers','tag_numbers.id','=','cois.tag_number_id')
    ->whereRaw("SUBSTRING_INDEX(tag_numbers.tag_number,'/',1) IN ('4-PSV-171','4-PSV-172','4-PSV-173','4-PSV-176','4-PSV-177','4-PSV-178','4-PSV-184')")
    ->select('monitoring_psv.id','tag_numbers.tag_number')
    ->get();

echo $rows->count() . PHP_EOL;
foreach ($rows as $r) {
    echo $r->id . ':' . $r->tag_number . PHP_EOL;
}
