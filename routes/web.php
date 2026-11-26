<?php

use App\Http\Controllers\FileAccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/file_historical_memorandum.zip', [FileAccessController::class, 'memorandumZip'])
    ->middleware('file.access:get_historical_memorandum');

Route::get('/file_lampiran_memo.zip', [FileAccessController::class, 'lampiranZip'])
    ->middleware('file.access:get_historical_memorandum_lampiran');

Route::get('/historical_memorandum/lampiran/{filename}', [FileAccessController::class, 'lampiran'])
    ->where('filename', '[^/]+')
    ->middleware('file.access:get_historical_memorandum_lampiran');

Route::get('/historical_memorandum/{filename}', [FileAccessController::class, 'memorandum'])
    ->where('filename', '[^/]+')
    ->middleware('file.access:get_historical_memorandum');
