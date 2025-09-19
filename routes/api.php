<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    UnitController,
    CategoryController,
    TypeController,
    Tag_numberController,
    ContractController,
    TerminController,
    TermBillingController,
    PloController,
    CoiController,
    SkhpController,
    SpkController,
    Spk_progressController,
    Lumpsum_progressController,
    ReportPloController,
    LampiranMemoController,
    AmandemenController,
    BreakdownReportController,
    ContractJasaController,
    HistoricalMemorandumController,
    DatasheetController,
    DeliveryMaterialController,
    GaDrawingController,
    EngineeringDataController,
    EventReadinessController,
    ExternalInspectionController,
    FabrikasiMaterialController,
    InternalInspectionController,
    JobPlanJasaController,
    JobPlanMaterialController,
    LampiranMocController,
    LaporanInspectionController,
    LogActivityController,
    MocController,
    NotifJasaController,
    NotifMaterialController,
    OnstreamInspectionController,
    PoMaterialController,
    PrJasaController,
    PrMaterialController,
    ProjectController,
    ReadinessJasaController,
    ReadinessMaterialController,
    RekomendasiJasaController,
    RekomendasiMaterialController,
    SurveillanceController,
    TenderJasaController,
    TenderMaterialController
};

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/total', [ProjectController::class, 'totalSize']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Auth)
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth:api'])->group(function () {

    // AUTH
    Route::post('/me', [AuthController::class, 'me']);

    // LOG ACTIVITIES
    Route::get('log_activities', [LogActivityController::class, 'index']);
    Route::get('log_activities/user', [LogActivityController::class, 'showByAllUsers']);
    Route::get('log_activities/user/{user_id}', [LogActivityController::class, 'showByUser']);

    Route::apiResource('units', UnitController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('types', TypeController::class)->only(['index', 'show']);
    Route::apiResource('tagnumbers', Tag_numberController::class)->only(['index', 'show']);

    /*
    |--------------------------------------------------------------------------
    | Role-based Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:1,99'])->group(function () {
        Route::apiResource('units', UnitController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('types', TypeController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('tagnumbers', Tag_numberController::class)->only(['store', 'update', 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Master Data & Utilities
    |--------------------------------------------------------------------------
    */
    Route::get('/activeunits', [UnitController::class, 'showByStatus']);
    Route::put('/units/nonactive/{id}', [UnitController::class, 'nonactive']);

    Route::get('/activecategories', [CategoryController::class, 'showByStatus']);
    Route::get('/categories/unit/{unitId}', [CategoryController::class, 'showByUnit']);
    Route::put('/categories/nonactive/{id}', [CategoryController::class, 'nonactive']);

    Route::get('/activetypes', [TypeController::class, 'showByStatus']);
    Route::get('/types/category/{categoryId}', [TypeController::class, 'showByCategory']);
    Route::put('/types/nonactive/{id}', [TypeController::class, 'nonactive']);

    Route::get('/tagnumbers/type/{typeId}', [Tag_numberController::class, 'showByType']);
    Route::get('/tagnumbers/typeunit/{typeId}/{unitId}', [Tag_numberController::class, 'showByTypeUnit']);
    Route::get('/tagnumbers/unit/{unitId}', [Tag_numberController::class, 'showByUnit']);
    Route::get('/tagnumbers/tag_number/{id}', [Tag_numberController::class, 'showByTagNumberId']);
    Route::get('/tagname', [Tag_numberController::class, 'showByTagNumber']);
    Route::put('/tagnumbers/nonactive/{id}', [Tag_numberController::class, 'nonactive']);
    Route::post('/tagnumbers/import', [Tag_numberController::class, 'import']);

    /*
    |--------------------------------------------------------------------------
    | Resource Routes pages
    |--------------------------------------------------------------------------
    */
    Route::apiResource('contract', ContractController::class);
    Route::apiResource('termin', TerminController::class);
    Route::apiResource('termbilling', TermBillingController::class);
    Route::apiResource('plo', PloController::class);
    Route::apiResource('coi', CoiController::class);
    Route::apiResource('skhp', SkhpController::class);
    Route::apiResource('moc', MocController::class);
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
    Route::apiResource('laporan_inspection', LaporanInspectionController::class);
    Route::apiResource('internal_inspection', InternalInspectionController::class);
    Route::apiResource('external_inspection', ExternalInspectionController::class);
    Route::apiResource('onstream_inspection', OnstreamInspectionController::class);
    Route::apiResource('surveillance', SurveillanceController::class);
    Route::apiResource('breakdown_report', BreakdownReportController::class);
    Route::apiResource('event_readiness', EventReadinessController::class);
    Route::apiResource('readiness_material', ReadinessMaterialController::class);
    Route::apiResource('rekomendasi_material', RekomendasiMaterialController::class);
    Route::apiResource('notif_material', NotifMaterialController::class);
    Route::apiResource('job_plan_material', JobPlanMaterialController::class);
    Route::apiResource('pr_material', PrMaterialController::class);
    Route::apiResource('tender_material', TenderMaterialController::class);
    Route::apiResource('po_material', PoMaterialController::class);
    Route::apiResource('fabrikasi_material', FabrikasiMaterialController::class);
    Route::apiResource('delivery_material', DeliveryMaterialController::class);
    Route::apiResource('readiness_jasa', ReadinessJasaController::class);
    Route::apiResource('rekomendasi_jasa', RekomendasiJasaController::class);
    Route::apiResource('notif_jasa', NotifJasaController::class);
    Route::apiResource('job_plan_jasa', JobPlanJasaController::class);
    Route::apiResource('pr_jasa', PrJasaController::class);
    Route::apiResource('tender_jasa', TenderJasaController::class);
    Route::apiResource('contract_jasa', ContractJasaController::class);
    

    /*
    |--------------------------------------------------------------------------
    | Custom Feature Routes
    |--------------------------------------------------------------------------
    */

    // User
    Route::put('/users/nonactive/{id}', [UserController::class, 'nonactive']);

    // COI
    Route::post('/coi/download', [CoiController::class, 'downloadCoiCertificates']);
    Route::put('/coi/deletefile/{id}', [CoiController::class, 'deleteFileCoi']);
    Route::get('/coi_countduedays', [CoiController::class, 'countCoiDueDays']);
    Route::get('/coi/tag_number/{id}', [CoiController::class, 'showByTagNumber']);

    // PLO
    Route::put('/plo/deletefile/{id}', [PloController::class, 'deleteFilePlo']);
    Route::post('/plo/download', [PloController::class, 'downloadPloCertificates']);
    Route::get('/plo_countduedays', [PloController::class, 'countPloDueDays']);

    // SKHP
    Route::post('/skhp/download', [SkhpController::class, 'downloadskhpCertificates']);
    Route::put('/skhp/deletefile/{id}', [SkhpController::class, 'deleteFileskhp']);
    Route::get('/skhp_countduedays', [SkhpController::class, 'countskhpDueDays']);

    // REPORT PLO 
    Route::get('/report_plos/{id}', [ReportPloController::class, 'showWithPloId']);

    // TERMIN
    Route::get('/termin/contract/{id}', [TerminController::class, 'showByContract']);

    // TERM BILLING
    Route::get('/termbilling/contract/{id}', [TermBillingController::class, 'showByContract']);

    // SPK
    Route::get('/spk/contract/{id}', [SpkController::class, 'showByContract']);

    // PROGRESS SPK
    Route::get('/spk_progress/spk/{id}', [Spk_progressController::class, 'showBySpk']);
    Route::get('/spk_progress/contract/{id}', [Spk_progressController::class, 'showByContract']);

    // PROGRESS LUMPSUM
    Route::get('/lumpsum_progress/contract/{id}', [Lumpsum_progressController::class, 'showByContract']);

    // AMANDEMEN
    Route::get('/amandemen/contract/{id}', [AmandemenController::class, 'showByContract']);

    // CONTRACT
    Route::get('/monitoring_contract', [ContractController::class, 'monitoring']);
    Route::put('contract/current_status/{id}', [ContractController::class, 'updateCurrentStatus']);

    // HISTORICAL MEMORANDUM LAMPIRAN
    Route::get('/historical_memorandum/lampiran/{id}', [LampiranMemoController::class, 'showWithHistoricalId']);

    // GA DRAWING
    Route::get('/ga_drawing/engineering/{id}', [GaDrawingController::class, 'showWithEngineeringDataId']);

    // DATASHEET
    Route::get('/datasheet/engineering/{id}', [DatasheetController::class, 'showWithEngineeringDataId']);

    // CONTRACTS BY USER
    Route::get('/contracts/user', [ContractController::class, 'contractsByUser']);

    //INTERNAL INSPECTION
    Route::get('/internal_inspection/laporan_inspection/{id}', [InternalInspectionController::class, 'showByLaporanInspection']);
    //EXTERNAL INSPECTION
    Route::get('/external_inspection/laporan_inspection/{id}', [ExternalInspectionController::class, 'showByLaporanInspection']);
    //ONSTREAM INSPECTION
    Route::get('/onstream_inspection/laporan_inspection/{id}', [OnstreamInspectionController::class, 'showByLaporanInspection']);
    //BREAKDOWN REPORT
    Route::get('/breakdown_report/laporan_inspection/{id}', [BreakdownReportController::class, 'showByLaporanInspection']);
    //SURVEILLANCE
    Route::get('/surveillance/laporan_inspection/{id}', [SurveillanceController::class, 'showByLaporanInspection']);
    //READINESS MATERIAL
    Route::get('/readiness_material/event/{id}', [ReadinessMaterialController::class, 'showByEvent']);
    //REKOMENDASI MATERIAL
    Route::get('/rekomendasi_material/readiness/{id}', [RekomendasiMaterialController::class, 'showByReadiness']);
    //NOTIF MATERIAL
    Route::get('/notif_material/readiness/{id}', [NotifMaterialController::class, 'showByReadiness']);
    //JOB PLAN MATERIAL
    Route::get('/job_plan_material/readiness/{id}', [JobPlanMaterialController::class, 'showByReadiness']);
    //PR MATERIAL
    Route::get('/pr_material/readiness/{id}', [PrMaterialController::class, 'showByReadiness']);
    //TENDER MATERIAL
    Route::get('/tender_material/readiness/{id}', [TenderMaterialController::class, 'showByReadiness']);
    //PO MATERIAL
    Route::get('/po_material/readiness/{id}', [PoMaterialController::class, 'showByReadiness']);
    //FABRIKASI MATERIAL
    Route::get('/fabrikasi_material/readiness/{id}', [FabrikasiMaterialController::class, 'showByReadiness']);
    //DELIVERY MATERIAL
    Route::get('/delivery_material/readiness/{id}', [DeliveryMaterialController::class, 'showByReadiness']);
    //READINESS JASA
    Route::get('/readiness_jasa/event/{id}', [ReadinessJasaController::class, 'showByEvent']);
});
