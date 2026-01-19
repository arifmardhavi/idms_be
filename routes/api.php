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
    HistoricalEquipmentController,
    InternalInspectionController,
    IzinDisnakerController,
    IzinOperasiController,
    IzinUsahaController,
    JobPlanJasaController,
    JobPlanMaterialController,
    LampiranMocController,
    LaporanInspectionController,
    LogActivityController,
    MdrFolderController,
    MdrItemController,
    MocController,
    NotifJasaController,
    NotifMaterialController,
    OnstreamInspectionController,
    OpenFileActivityController,
    OverhaulController,
    P_IdController,
    PirController,
    PoMaterialController,
    PreventiveController,
    PrJasaController,
    PrMaterialController,
    ProjectController,
    ReadinessJasaController,
    ReadinessMaterialController,
    RekomendasiJasaController,
    RekomendasiMaterialController,
    ReportCoiController,
    ReportIzinDisnakerController,
    ReportIzinOperasiController,
    SertifikatKalibrasiController,
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
        Route::get('exportunits', [UnitController::class, 'exportUnit']);
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
    Route::post('/tagnumbers/import_update', [Tag_numberController::class, 'importUpdate']);

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
    Route::apiResource('sertifikat_kalibrasi', SertifikatKalibrasiController::class);
    Route::apiResource('moc', MocController::class);
    Route::apiResource('spk', SpkController::class);
    Route::apiResource('spk_progress', Spk_progressController::class);
    Route::apiResource('lumpsum_progress', Lumpsum_progressController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('report_plo', ReportPloController::class);
    Route::apiResource('report_coi', ReportCoiController::class);
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
    Route::apiResource('overhaul', OverhaulController::class);
    Route::apiResource('preventive', PreventiveController::class);
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
    Route::apiResource('open_file_activity', OpenFileActivityController::class);
    Route::apiResource('p_id', P_IdController::class);
    Route::apiResource('izin_usaha', IzinUsahaController::class);
    Route::apiResource('izin_disnaker', IzinDisnakerController::class);
    Route::apiResource('report_izin_disnaker', ReportIzinDisnakerController::class);
    Route::apiResource('izin_operasi', IzinOperasiController::class);
    Route::apiResource('report_izin_operasi', ReportIzinOperasiController::class);
    Route::apiResource('pir', PirController::class);
    Route::apiResource('mdr_folder', MdrFolderController::class);
    Route::apiResource('mdr_item', MdrItemController::class);
    Route::apiResource('historical_equipment', HistoricalEquipmentController::class);

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
    // IZIN DISNAKER
    Route::post('/izin_disnaker/download', [IzinDisnakerController::class, 'downloadIzinDisnakerCertificates']);
    Route::put('/izin_disnaker/deletefile/{id}', [IzinDisnakerController::class, 'deleteFileIzinDisnaker']);
    Route::get('/izin_disnaker_countduedays', [IzinDisnakerController::class, 'countIzinDisnakerDueDays']);
    Route::get('/izin_disnaker/tag_number/{id}', [IzinDisnakerController::class, 'showByTagNumber']);
    // PLO
    Route::put('/plo/deletefile/{id}', [PloController::class, 'deleteFilePlo']);
    Route::post('/plo/download', [PloController::class, 'downloadPloCertificates']);
    Route::get('/plo_countduedays', [PloController::class, 'countPloDueDays']);
    // IZIN OPERASI
    Route::put('/izin_operasi/deletefile/{id}', [IzinOperasiController::class, 'deleteFileIzinOperasi']);
    Route::post('/izin_operasi/download', [IzinOperasiController::class, 'downloadIzinOperasiCertificates']);
    Route::get('/izin_operasi_countduedays', [IzinOperasiController::class, 'countIzinOperasiDueDays']);

    // SKHP
    Route::post('/skhp/download', [SkhpController::class, 'downloadskhpCertificates']);
    Route::put('/skhp/deletefile/{id}', [SkhpController::class, 'deleteFileskhp']);
    Route::get('/skhp_countduedays', [SkhpController::class, 'countskhpDueDays']);
    
    // SERTIFIKAT KALIBRASI
    Route::post('/sertifikat_kalibrasi/download', [SertifikatKalibrasiController::class, 'downloadSertifikatKalibrasiCertificates']);
    Route::put('/sertifikat_kalibrasi/deletefile/{id}', [SertifikatKalibrasiController::class, 'deleteFileSertifikatKalibrasi']);
    Route::get('/sertifikat_kalibrasi_countduedays', [SertifikatKalibrasiController::class, 'countSertifikatKalibrasiDueDays']);

    // REPORT PLO 
    Route::get('/report_plos/{id}', [ReportPloController::class, 'showWithPloId']);
    // REPORT IZIN DISNAKER 
    Route::get('/report_izin_disnakers/{id}', [ReportIzinDisnakerController::class, 'showWithIzinDisnakerId']);
    // REPORT COI 
    Route::get('/report_cois/{id}', [ReportCoiController::class, 'showWithCoiId']);
    // REPORT IZIN OPERASI 
    Route::get('/report_izin_operasis/{id}', [ReportIzinOperasiController::class, 'showWithIzinOperasiId']);

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
    Route::get('contracts/po_material_type', [ContractController::class, 'showByPoMaterialType']);
    Route::get('contracts/un_po_material_type', [ContractController::class, 'showByUnPoMaterialType']);

    // HISTORICAL MEMORANDUM & LAMPIRAN
    Route::get('/historical_memorandum/lampiran/{id}', [LampiranMemoController::class, 'showWithHistoricalId']);
    Route::post('/lampiran_memo/download', [LampiranMemoController::class, 'downloadLampiranMemoFiles']);
    Route::post('/historical_memorandum/download', [HistoricalMemorandumController::class, 'downloadHistoricalMemorandumFiles']);

    // GA DRAWING
    Route::get('/ga_drawing/engineering/{id}', [GaDrawingController::class, 'showWithEngineeringDataId']);

    // DATASHEET
    Route::get('/datasheet/engineering/{id}', [DatasheetController::class, 'showWithEngineeringDataId']);
    
    // MDR
    Route::get('/mdr_folder/engineering/{id}', [MdrFolderController::class, 'showByEngineering']);
    Route::get('/mdr_item/folder/{id}', [MdrItemController::class, 'showByFolder']);

    // CONTRACTS BY USER
    Route::get('/contracts/user', [ContractController::class, 'contractsByUser']);

    // DASHBOARD READINESS MATERIAL
    Route::get('/readiness_material/dashboard/{id}', [ReadinessMaterialController::class, 'dashboard']);
    // DASHBOARD READINESS JASA
    Route::get('/readiness_jasa/dashboard/{id}', [ReadinessJasaController::class, 'dashboard']);
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
    //PREVENTIVE
    Route::get('/preventive/laporan_inspection/{id}', [PreventiveController::class, 'showByLaporanInspection']);
    //OVERHAUL
    Route::get('/overhaul/laporan_inspection/{id}', [OverhaulController::class, 'showByLaporanInspection']);
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
    // OPEN FILE ACTIVITY
    Route::get('/open_file_activity/user/{id}', [OpenFileActivityController::class, 'showByUserId']);
   

});
