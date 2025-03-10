<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CoiController;
use App\Http\Controllers\PloController;
use App\Http\Controllers\SkhpController;
use App\Http\Controllers\Tag_numberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TypeController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('units', UnitController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('tagnumbers', Tag_numberController::class);
    Route::apiResource('plo', PloController::class);
    Route::apiResource('coi', CoiController::class);
    Route::apiResource('skhp', SkhpController::class);
    Route::apiResource('users', UserController::class);
// });
    // Route::patch('/plo/{id}', function (Request $request, $id) {
    //     return response()->json([
    //         'id' => $id,
    //         'data' => $request->all(),
    //     ]);
    //  });

    // Unit
    Route::put('/units/nonactive/{id}', [UnitController::class, 'nonactive']);

    // Category
    Route::get('/categories/unit/{unitId}', [CategoryController::class, 'showByUnit']); // show category by unit_id
    Route::put('/categories/nonactive/{id}', [CategoryController::class, 'nonactive']); // nonactive category

    // Type
    Route::get('/types/category/{categoryId}', [TypeController::class, 'showByCategory']); // show type by category_id
    Route::put('/types/nonactive/{id}', [TypeController::class, 'nonactive']); // nonactive type

    // Tag_number
    Route::get('/tagnumbers/type/{typeId}', [Tag_numberController::class, 'showByType']); // show tag_number by type_id
    Route::get('/tagnumbers/typeunit/{typeId}/{unitId}', [Tag_numberController::class, 'showByTypeUnit']); // show tag_number by type_id
    Route::get('/tagnumbers/tag_number/{id}', [Tag_numberController::class, 'showByTagNumberId']); // show tag_number by ID with unit
    Route::get('/tagname', [Tag_numberController::class, 'showByTagNumber']); // show tag_number by tag_number
    Route::put('/tagnumbers/nonactive/{id}', [Tag_numberController::class, 'nonactive']); // nonactive tag_number


    // COI
    Route::post('/coi/download', [CoiController::class, 'downloadCoiCertificates']); // multiple download COI certificates
    Route::put('/coi/deletefile/{id}', [CoiController::class, 'deleteFileCoi']); // delete download COI certificates
    Route::get('/coi_countduedays', [CoiController::class, 'countCoiDueDays']); // count coi due days and grouping
    // PLO
    Route::put('/plo/deletefile/{id}', [PloController::class, 'deleteFilePlo']); // delete download PLO certificates
    Route::post('/plo/download', [PloController::class, 'downloadPloCertificates']); // multiple download PLO certificates
    Route::get('/plo_countduedays', [PloController::class, 'countPloDueDays']); // count plo due days and grouping
    // SKHP
    Route::post('/skhp/download', [SkhpController::class, 'downloadskhpCertificates']); // multiple download COI certificates
    Route::put('/skhp/deletefile/{id}', [SkhpController::class, 'deleteFileskhp']); // delete download COI certificates
    Route::get('/skhp_countduedays', [SkhpController::class, 'countskhpDueDays']); // count coi due days and grouping
    


Route::post('/login', [UserController::class, 'login']);