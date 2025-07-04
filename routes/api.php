<?php

use App\Http\Controllers\AmandemenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CoiController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DatasheetController;
use App\Http\Controllers\EngineeringDataController;
use App\Http\Controllers\GaDrawingController;
use App\Http\Controllers\HistoricalMemorandumController;
use App\Http\Controllers\LampiranMemoController;
use App\Http\Controllers\Lumpsum_progressController;
use App\Http\Controllers\PloController;
use App\Http\Controllers\ReportPloController;
use App\Http\Controllers\SkhpController;
use App\Http\Controllers\Spk_progressController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\Tag_numberController;
use App\Http\Controllers\TermBillingController;
use App\Http\Controllers\TerminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\TypeController;
use App\Models\Termin;
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
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth:api', 'role:1,99'])->group(function () {
    Route::apiResource('units', UnitController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('tagnumbers', Tag_numberController::class);
});
    Route::apiResource('contract', ContractController::class);
    Route::apiResource('termin', TerminController::class);
    Route::apiResource('termbilling', TermBillingController::class);
    Route::apiResource('plo', PloController::class);
    Route::apiResource('coi', CoiController::class);
    Route::apiResource('skhp', SkhpController::class);
    Route::apiResource('spk', SpkController::class);
    Route::apiResource('spk_progress', Spk_progressController::class);
    Route::apiResource('lumpsum_progress', Lumpsum_progressController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('report_plo', ReportPloController::class);
    Route::apiResource('lampiran_memo', LampiranMemoController::class);
    Route::apiResource('amandemen', AmandemenController::class);
    Route::apiResource('historical_memorandum', HistoricalMemorandumController::class);
    Route::apiResource('datasheet', DatasheetController::class);
    Route::apiResource('ga_drawing', GaDrawingController::class);
    Route::apiResource('engineering_data', EngineeringDataController::class);
   

    // Unit
    Route::get('/activeunits', [UnitController::class, 'showByStatus']);
    Route::put('/units/nonactive/{id}', [UnitController::class, 'nonactive']);
    // User
    Route::put('/users/nonactive/{id}', [UserController::class, 'nonactive']);

    // Category
    Route::get('/activecategories', [CategoryController::class, 'showByStatus']);
    Route::get('/categories/unit/{unitId}', [CategoryController::class, 'showByUnit']); // show category by unit_id
    Route::put('/categories/nonactive/{id}', [CategoryController::class, 'nonactive']); // nonactive category

    // Type
    Route::get('/activetypes', [TypeController::class, 'showByStatus']);
    Route::get('/types/category/{categoryId}', [TypeController::class, 'showByCategory']); // show type by category_id
    Route::put('/types/nonactive/{id}', [TypeController::class, 'nonactive']); // nonactive type

    // Tag_number
    Route::get('/tagnumbers/type/{typeId}', [Tag_numberController::class, 'showByType']); // show tag_number by type_id
    Route::get('/tagnumbers/typeunit/{typeId}/{unitId}', [Tag_numberController::class, 'showByTypeUnit']); // show tag_number by type_id
    Route::get('/tagnumbers/unit/{unitId}', [Tag_numberController::class, 'showByUnit']); // show tag_number by type_id
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
    
    // REPORT PLO 
    Route::get('/report_plos/{id}', [ReportPloController::class, 'showWithPloId']);
    // TERMIN 
    Route::get('/termin/contract/{id}', [TerminController::class, 'showByContract']);
    // TERM BILLING 
    Route::get('/termbilling/contract/{id}', [TermBillingController::class, 'showByContract']);
    // SPK 
    Route::get('/spk/contract/{id}', [SpkController::class, 'showByContract']);
    // PROGRESS PEKERJAAN SPK 
    Route::get('/spk_progress/spk/{id}', [Spk_progressController::class, 'showBySpk']);
    Route::get('/spk_progress/contract/{id}', [Spk_progressController::class, 'showByContract']);
    // PROGRESS PEKERJAAN LUMPSUM 
    Route::get('/lumpsum_progress/contract/{id}', [Lumpsum_progressController::class, 'showByContract']);
    // AMANDEMEN
    Route::get('/amandemen/contract/{id}', [AmandemenController::class, 'showByContract']);
    // CONTRACT
    Route::get('/monitoring_contract', [ContractController::class, 'monitoring']);
    // HISTORICAL MEMORANDUM LAMPIRAN
    Route::get('/historical_memorandum/lampiran/{id}', [LampiranMemoController::class, 'showWithHistoricalId']);
    // GA DRAWING
    Route::get('/ga_drawing/engineering/{id}', [GaDrawingController::class, 'showWithEngineeringDataId']);
    // DATASHEET
    Route::get('/datasheet/engineering/{id}', [DatasheetController::class, 'showWithEngineeringDataId']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/contracts/user', [ContractController::class, 'contractsByUser']);
    });
