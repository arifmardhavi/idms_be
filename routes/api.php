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
    AmandemenNewController,
    BapkCoiController,
    BapkPloController,
    BreakdownReportController,
    ContractJasaController,
    ContractNewController,
    HistoricalMemorandumController,
    DatasheetController,
    DeliveryMaterialController,
    DeliveryMaterialOhController,
    GaDrawingController,
    EngineeringDataController,
    EventReadinessController,
    EventReadinessOhController,
    ExternalInspectionController,
    FabrikasiMaterialController,
    FabrikasiMaterialOhController,
    FeatureController,
    HakAksesController,
    HistoricalEquipmentController,
    InternalInspectionController,
    IzinDisnakerController,
    IzinOperasiController,
    IzinUsahaController,
    JobPlanJasaController,
    JobPlanMaterialController,
    JobPlanMaterialOhController,
    LampiranMocController,
    LaporanInspectionController,
    LogActivityController,
    LumpsumProgressNewController,
    MdrFolderController,
    MdrItemController,
    MocController,
    NibController,
    NotifJasaController,
    NotifMaterialController,
    NotifMaterialOhController,
    OnstreamInspectionController,
    OpenFileActivityController,
    OverhaulController,
    P_IdController,
    PirController,
    PoMaterialController,
    PoMaterialOhController,
    PreventiveController,
    PrJasaController,
    PrMaterialController,
    PrMaterialOhController,
    ProjectController,
    ReadinessJasaController,
    ReadinessMaterialController,
    ReadinessMaterialOhController,
    RekomendasiJasaController,
    RekomendasiMaterialController,
    RekomendasiMaterialOhController,
    ReportCoiController,
    ReportIzinDisnakerController,
    ReportIzinOperasiController,
    SertifikatKalibrasiController,
    SpkNewController,
    SpkProgressNewController,
    SurveillanceController,
    TenderJasaController,
    TenderMaterialController,
    TenderMaterialOhController,
    TerminNewController,
    TerminReceiptController,
    UserHakAksesController
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
    Route::apiResource('event_readiness_oh', EventReadinessOhController::class);
    Route::apiResource('readiness_material', ReadinessMaterialController::class);
    Route::apiResource('readiness_material_oh', ReadinessMaterialOhController::class);
    Route::apiResource('rekomendasi_material', RekomendasiMaterialController::class);
    Route::apiResource('notif_material', NotifMaterialController::class);
    Route::apiResource('notif_material_oh', NotifMaterialOhController::class);
    Route::apiResource('job_plan_material', JobPlanMaterialController::class);
    Route::apiResource('job_plan_material_oh', JobPlanMaterialOhController::class);
    Route::apiResource('pr_material', PrMaterialController::class);
    Route::apiResource('pr_material_oh', PrMaterialOhController::class);
    Route::apiResource('tender_material', TenderMaterialController::class);
    Route::apiResource('tender_material_oh', TenderMaterialOhController::class);
    Route::apiResource('po_material', PoMaterialController::class);
    Route::apiResource('po_material_oh', PoMaterialOhController::class);
    Route::apiResource('fabrikasi_material', FabrikasiMaterialController::class);
    Route::apiResource('fabrikasi_material_oh', FabrikasiMaterialOhController::class);
    Route::apiResource('delivery_material', DeliveryMaterialController::class);
    Route::apiResource('delivery_material_oh', DeliveryMaterialOhController::class);
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
    Route::apiResource('contract_new', ContractNewController::class);
    Route::apiResource('termin_new', TerminNewController::class);
    Route::apiResource('termin_receipt', TerminReceiptController::class);
    Route::apiResource('lumpsum_progress_new', LumpsumProgressNewController::class);
    Route::apiResource('spk_new', SpkNewController::class);
    Route::apiResource('spk_progress_new', SpkProgressNewController::class);
    Route::apiResource('amandemen_new', AmandemenNewController::class);
    Route::apiResource('nib', NibController::class);
    Route::apiResource('bapk_coi', BapkCoiController::class);
    Route::apiResource('bapk_plo', BapkPloController::class);
    Route::apiResource('features', FeatureController::class);
    Route::apiResource('hak_akses', HakAksesController::class);
    Route::apiResource('user_hak_akses', UserHakAksesController::class);
    Route::apiResource('rekomendasi_material_oh', RekomendasiMaterialOhController::class);

    /*
    |--------------------------------------------------------------------------
    | Custom Feature Routes
    |--------------------------------------------------------------------------
    */

    // download file
    Route::get('/p_id/download_file/{id}', [P_IdController::class, 'downloadPIdFile']); // Download P_Id File
    Route::get('/plo/download_file/{id}', [PloController::class, 'downloadPloFile']); // Download PLO File
    Route::get('/report_plo/download_file/{id}', [ReportPloController::class, 'downloadReportPloFile']); // Download Report PLO File
    Route::get('/bapk_plo/download_file/{id}', [BapkPloController::class, 'downloadBapkPloFile']); // Download BAPK PLO File
    Route::get('/coi/download_file/{id}', [CoiController::class, 'downloadCoiFile']); // Download COI File
    Route::get('/report_coi/download_file/{id}', [ReportCoiController::class, 'downloadReportCoiFile']); // Download Report COI File
    Route::get('/bapk_coi/download_file/{id}', [BapkCoiController::class, 'downloadBapkCoiFile']); // Download BAPK COI File
    Route::get('/skhp/download_file/{id}', [SkhpController::class, 'downloadSkhpFile']); // Download SKHP File
    Route::get('/sertifikat_kalibrasi/download_file/{id}', [SertifikatKalibrasiController::class, 'downloadSertifikatKalibrasiFile']); // Download Sertifikat Kalibrasi File
    Route::get('/izin_usaha/download_file/{id}', [IzinUsahaController::class, 'downloadIzinUsahaFile']); // Download Izin Usaha File
    Route::get('/nib/download_file/{id}', [NibController::class, 'downloadNibFile']); // Download NIB File
    Route::get('/izin_operasi/download_file/{id}', [IzinOperasiController::class, 'downloadIzinOperasiFile']); // Download Izin Operasi File
    Route::get('/report_izin_operasi/download_file/{id}', [ReportIzinOperasiController::class, 'downloadReportIzinOperasiFile']); // Download Report Izin Operasi File
    Route::get('/izin_disnaker/download_file/{id}', [IzinDisnakerController::class, 'downloadIzinDisnakerFile']); // Download Izin Disnaker File
    Route::get('/report_izin_disnaker/download_file/{id}', [ReportIzinDisnakerController::class, 'downloadReportIzinDisnakerFile']); // Download Report Izin Disnaker File
    Route::get('contract_new/download/{id}', [ContractNewController::class, 'downloadContractFile']); // Download Contract New File
    Route::get('/spk/download_file/{id}', [SpkNewController::class, 'downloadSpkFile']); // Download SPK File
    Route::get('/spk_progress/download_file/{id}', [SpkProgressNewController::class, 'downloadSpkProgressFile']); // Download SPK Progress File
    Route::get('/termin_receipt/download_file/{id}', [TerminReceiptController::class, 'downloadTerminReceiptFile']); // Download Termin Receipt File
    Route::get('/lumpsum_progress_new/download_file/{id}', [LumpsumProgressNewController::class, 'downloadLumpsumProgressFile']); // Download Lumpsum Progress File
    Route::get('/amandemen_new/download_file/{id}', [AmandemenNewController::class, 'downloadAmandemenFile']); // Download Amandemen File
    Route::get('/historical_memorandum/download_file/{id}', [HistoricalMemorandumController::class, 'downloadHistoricalMemorandumFile']); // Download Historical Memorandum File
    Route::get('/lampiran_memo/download_file/{id}', [LampiranMemoController::class, 'downloadLampiranMemoFile']); // Download Lampiran Memo File


    // User
    Route::put('/users/nonactive/{id}', [UserController::class, 'nonactive']);
    // Feature
    Route::get('/feature/group', [FeatureController::class, 'showByGroup']);
    Route::get('/user_hak_akses/user/{id}', [UserHakAksesController::class, 'showByUser']);

    // COI
    Route::post('/coi/download', [CoiController::class, 'downloadCoiCertificates']);
    Route::put('/coi/deletefile/{id}', [CoiController::class, 'deleteFileCoi']);
    Route::get('/coi_countduedays', [CoiController::class, 'countCoiDueDays']);
    Route::get('/coi_filter', [CoiController::class, 'filteringCoi']);
    Route::get('/coi/tag_number/{id}', [CoiController::class, 'showByTagNumber']);
    Route::get('/bapk_cois/{id}', [BapkCoiController::class, 'showByCoi']);
    // IZIN DISNAKER
    Route::post('/izin_disnaker/download', [IzinDisnakerController::class, 'downloadIzinDisnakerCertificates']);
    Route::put('/izin_disnaker/deletefile/{id}', [IzinDisnakerController::class, 'deleteFileIzinDisnaker']);
    Route::get('/izin_disnaker_countduedays', [IzinDisnakerController::class, 'countIzinDisnakerDueDays']);
    Route::get('/izin_disnaker/tag_number/{id}', [IzinDisnakerController::class, 'showByTagNumber']);
    // PLO
    Route::put('/plo/deletefile/{id}', [PloController::class, 'deleteFilePlo']);
    Route::post('/plo/download', [PloController::class, 'downloadPloCertificates']);
    Route::get('/plo_countduedays', [PloController::class, 'countPloDueDays']);
    Route::get('/bapk_plos/{id}', [BapkPloController::class, 'showByPlo']);
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
    // TERMIN NEW
    Route::get('/termin_new/contract/{id}', [TerminNewController::class, 'showByContract']);

    // TERM BILLING
    Route::get('/termbilling/contract/{id}', [TermBillingController::class, 'showByContract']);
    // TERMIN RECEIPT
    Route::get('/termin_receipt/contract/{id}', [TerminReceiptController::class, 'showByContract']);

    // SPK
    Route::get('/spk/contract/{id}', [SpkController::class, 'showByContract']);
    // SPK NEW
    Route::get('/spk_new/contract/{id}', [SpkNewController::class, 'showByContract']);

    // PROGRESS SPK
    Route::get('/spk_progress/spk/{id}', [Spk_progressController::class, 'showBySpk']);
    Route::get('/spk_progress/contract/{id}', [Spk_progressController::class, 'showByContract']);
    // PROGRESS SPK NEW
    Route::get('/spk_progress_new/spk/{id}', [SpkProgressNewController::class, 'showBySpk']);
    Route::get('/spk_progress_new/contract/{id}', [SpkProgressNewController::class, 'showByContract']);

    // PROGRESS LUMPSUM
    Route::get('/lumpsum_progress/contract/{id}', [Lumpsum_progressController::class, 'showByContract']);
    // PROGRESS LUMPSUM NEW
    Route::get('/lumpsum_progress_new/contract/{id}', [LumpsumProgressNewController::class, 'showByContract']);

    // AMANDEMEN
    Route::get('/amandemen/contract/{id}', [AmandemenController::class, 'showByContract']);
    // AMANDEMEN NEW
    Route::get('/amandemen_new/contract/{id}', [AmandemenNewController::class, 'showByContract']);

    // CONTRACT
    Route::get('/monitoring_contract', [ContractController::class, 'monitoring']);
    Route::put('contract/current_status/{id}', [ContractController::class, 'updateCurrentStatus']);
    Route::get('contracts/po_material_type', [ContractController::class, 'showByPoMaterialType']);
    Route::get('contracts/un_po_material_type', [ContractController::class, 'showByUnPoMaterialType']);

    // CONTRACT NEW
    Route::get('monitoring_contract_new', [ContractNewController::class, 'monitoringContract']);
    Route::put('contract_new/current_status/{id}', [ContractNewController::class, 'updateCurrentStatus']);
    Route::put('contract_new/tkdn/{id}', [ContractNewController::class, 'updateTkdn']);
    Route::get('contract_new/lumpsum_progress/{id}', [ContractNewController::class, 'contractLumpsumProgress']);
    Route::get('contract_new_po_material_type', [ContractNewController::class, 'showByPoMaterialType']);
    Route::get('contract_new_un_po_material_type', [ContractNewController::class, 'showByUnPoMaterialType']);
    Route::get('/contract_new_user', [ContractNewController::class, 'contractsByUser']);

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
    Route::put('event_readiness/status/{id}', [EventReadinessController::class, 'updateStatus']);
    Route::get('/readiness_material/dashboard/{id}', [ReadinessMaterialController::class, 'dashboard']);
    Route::put('readiness_material/current_status/{id}', [ReadinessMaterialController::class, 'updateCurrentStatus']);
    Route::put('readiness_material/status/{id}', [ReadinessMaterialController::class, 'updateStatus']);
    // DASHBOARD READINESS MATERIAL OH
    Route::put('event_readiness_oh/status/{id}', [EventReadinessOhController::class, 'updateStatus']);
    // Route::get('/readiness_material_oh/dashboard/{id}', [ReadinessMaterialOhController::class, 'dashboard']);
    Route::put('readiness_material_oh/current_status/{id}', [ReadinessMaterialOhController::class, 'updateCurrentStatus']);
    Route::put('readiness_material_oh/status/{id}', [ReadinessMaterialOhController::class, 'updateStatus']);
    // DASHBOARD READINESS JASA
    Route::get('/readiness_jasa/dashboard/{id}', [ReadinessJasaController::class, 'dashboard']);
    Route::put('readiness_jasa/status/{id}', [ReadinessJasaController::class, 'updateStatus']);
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
    //READINESS MATERIAL OH
    Route::get('/readiness_material_oh/event/{id}', [ReadinessMaterialOhController::class, 'showByEvent']);
    //REKOMENDASI MATERIAL
    Route::get('/rekomendasi_material/readiness/{id}', [RekomendasiMaterialController::class, 'showByReadiness']);
    Route::get('/rekomendasi_material_oh/readiness/{id}', [RekomendasiMaterialOhController::class, 'showByReadiness']);
    //NOTIF MATERIAL
    Route::get('/notif_material/readiness/{id}', [NotifMaterialController::class, 'showByReadiness']);
    //NOTIF MATERIAL OH
    Route::get('/notif_material_oh/readiness/{id}', [NotifMaterialOhController::class, 'showByReadiness']);
    //JOB PLAN MATERIAL
    Route::get('/job_plan_material/readiness/{id}', [JobPlanMaterialController::class, 'showByReadiness']);
    //JOB PLAN MATERIAL OH
    Route::get('/job_plan_material_oh/readiness/{id}', [JobPlanMaterialOhController::class, 'showByReadiness']);
    //PR MATERIAL
    Route::get('/pr_material/readiness/{id}', [PrMaterialController::class, 'showByReadiness']);
    //PR MATERIAL OH
    Route::get('/pr_material_oh/readiness/{id}', [PrMaterialOhController::class, 'showByReadiness']);
    //TENDER MATERIAL
    Route::get('/tender_material/readiness/{id}', [TenderMaterialController::class, 'showByReadiness']);
    //TENDER MATERIAL OH
    Route::get('/tender_material_oh/readiness/{id}', [TenderMaterialOhController::class, 'showByReadiness']);
    //PO MATERIAL
    Route::get('/po_material/readiness/{id}', [PoMaterialController::class, 'showByReadiness']);
    //PO MATERIAL OH
    Route::get('/po_material_oh/readiness/{id}', [PoMaterialOhController::class, 'showByReadiness']);
    //FABRIKASI MATERIAL
    Route::get('/fabrikasi_material/readiness/{id}', [FabrikasiMaterialController::class, 'showByReadiness']);
    Route::get('/fabrikasi_material_oh/readiness/{id}', [FabrikasiMaterialOhController::class, 'showByReadiness']);
    //DELIVERY MATERIAL
    Route::get('/delivery_material/readiness/{id}', [DeliveryMaterialController::class, 'showByReadiness']);
    Route::get('/delivery_material_oh/readiness/{id}', [DeliveryMaterialOhController::class, 'showByReadiness']);
    //READINESS JASA
    Route::get('/readiness_jasa/event/{id}', [ReadinessJasaController::class, 'showByEvent']);
    // OPEN FILE ACTIVITY
    Route::get('/open_file_activity/user/{id}', [OpenFileActivityController::class, 'showByUserId']);


});
