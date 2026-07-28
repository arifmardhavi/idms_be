<?php

namespace Tests\Feature;

use App\Helpers\BusinessPeriod;
use App\Models\MonitoringEquipment;
use App\Models\MonitoringEquipmentLog;
use App\Models\Tag_number;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MonitoringEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;
    protected array $tagIds = [];
    protected string $periodCode;
    protected array $prev1;
    protected array $prev2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodCode = BusinessPeriod::current()['code'];
        $this->prev1 = BusinessPeriod::previous(1);
        $this->prev2 = BusinessPeriod::previous(2);

        $user = User::create([
            'fullname' => 'Test User',
            'email' => 'test@test.com',
            'username' => 'testuser',
            'password' => 'password',
            'level_user' => 1,
            'status' => 1,
        ]);

        $this->token = JWTAuth::fromUser($user);
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    protected function seedTagNumbers(int $count = 100): array
    {
        $category = \App\Models\Category::create([
            'category_name' => 'Test Category',
            'status' => 1,
        ]);

        $unit = \App\Models\Unit::create([
            'unit_name' => 'Test Unit',
            'status' => 1,
        ]);

        $type = \App\Models\Type::create([
            'type_name' => 'Test Type',
            'category_id' => $category->id,
            'status' => 1,
        ]);

        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $tag = Tag_number::create([
                'unit_id' => $unit->id,
                'type_id' => $type->id,
                'tag_number' => 'TAG-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => 1,
            ]);
            $ids[] = $tag->id;
        }
        $this->tagIds = $ids;
        return $ids;
    }

    protected function createLogs(array $tagIds, string $periodCode, array $periodDates, array $overrides = []): void
    {
        $logData = array_merge([
            'status' => 'High',
            'kondisi_peralatan' => 'Baik',
        ], $overrides);

        foreach ($tagIds as $tagId) {
            MonitoringEquipmentLog::create(array_merge([
                'tag_number_id' => $tagId,
                'period_code' => $periodCode,
                'period_start' => $periodDates['start'],
                'period_end' => $periodDates['end'],
            ], $logData));
        }
    }

    protected function createEquipment(array $tagIds, array $overrides = []): void
    {
        foreach ($tagIds as $tagId) {
            MonitoringEquipment::updateOrCreate(
                ['tag_number_id' => $tagId],
                array_merge([
                    'status' => 'High',
                    'kondisi_peralatan' => 'Baik',
                ], $overrides)
            );
        }
    }

    protected function makeImportFile(array $tagIds, array $overrides = []): UploadedFile
    {
        $headers = [
            'tag_number', 'kondisi_peralatan', 'status', 'jenis_kerusakan',
            'penyebab', 'penanganan_sementara', 'perbaikan_permanen',
            'progress_perbaikan_permanen', 'kendala_perbaikan',
            'estimasi_perbaikan', 'target',
        ];

        $rows = [];
        foreach ($tagIds as $i => $tagId) {
            $tag = Tag_number::find($tagId);
            $rows[] = array_merge([
                'tag_number' => $tag->tag_number,
                'kondisi_peralatan' => 'Perlu Perbaikan',
                'status' => 'Low',
                'jenis_kerusakan' => null,
                'penyebab' => null,
                'penanganan_sementara' => null,
                'perbaikan_permanen' => null,
                'progress_perbaikan_permanen' => null,
                'kendala_perbaikan' => null,
                'estimasi_perbaikan' => null,
                'target' => null,
            ], $overrides);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, $rowIndex + 2, $row[$header] ?? null);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'import_test_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        $uploaded = new UploadedFile($tempFile, 'test_import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        return $uploaded;
    }

    // =========================================================================
    // IMPORT TESTS
    // =========================================================================

    public function test_import_fills_current_period_with_all_equipment(): void
    {
        $tagIds = $this->seedTagNumbers(100);
        $this->createEquipment($tagIds);

        $oldPeriod = BusinessPeriod::previous(4);
        $this->createLogs($tagIds, $oldPeriod['code'], [
            'start' => $oldPeriod['start'],
            'end' => $oldPeriod['end'],
        ]);

        $onlyFirst10 = array_slice($tagIds, 0, 10);

        $file = $this->makeImportFile($onlyFirst10);

        $response = $this->postJson('/api/monitoring_equipment/import', [
            'file' => $file,
        ], $this->authHeaders());

        $response->assertStatus(200);

        $currentCount = MonitoringEquipmentLog::where('period_code', $this->periodCode)->count();
        $this->assertEquals(100, $currentCount, 'Current period should have all 100 equipment logs');
    }

    public function test_import_fills_previous_periods_when_empty(): void
    {
        $tagIds = $this->seedTagNumbers(100);

        $oldPeriod = BusinessPeriod::previous(4);
        $this->createEquipment($tagIds);
        $this->createLogs($tagIds, $oldPeriod['code'], [
            'start' => $oldPeriod['start'],
            'end' => $oldPeriod['end'],
        ]);

        $file = $this->makeImportFile(array_slice($tagIds, 0, 5));

        $this->postJson('/api/monitoring_equipment/import', [
            'file' => $file,
        ], $this->authHeaders());

        $prev1Count = MonitoringEquipmentLog::where('period_code', $this->prev1['code'])->count();
        $this->assertEquals(100, $prev1Count, 'previous(1) should have all 100 equipment from latest available');

        $prev2Count = MonitoringEquipmentLog::where('period_code', $this->prev2['code'])->count();
        $this->assertEquals(100, $prev2Count, 'previous(2) should have all 100 equipment from latest available');
    }

    public function test_import_fills_from_data_months_ago_when_consecutive_periods_empty(): void
    {
        $tagIds = $this->seedTagNumbers(50);

        $period5MonthsAgo = BusinessPeriod::previous(5);
        $this->createEquipment($tagIds, ['status' => 'Medium', 'kondisi_peralatan' => 'Sedang']);
        $this->createLogs($tagIds, $period5MonthsAgo['code'], [
            'start' => $period5MonthsAgo['start'],
            'end' => $period5MonthsAgo['end'],
        ], ['status' => 'Medium', 'kondisi_peralatan' => 'Sedang']);

        $file = $this->makeImportFile(array_slice($tagIds, 0, 3));

        $this->postJson('/api/monitoring_equipment/import', [
            'file' => $file,
        ], $this->authHeaders());

        $prev1Count = MonitoringEquipmentLog::where('period_code', $this->prev1['code'])->count();
        $this->assertEquals(50, $prev1Count);

        $prev2Count = MonitoringEquipmentLog::where('period_code', $this->prev2['code'])->count();
        $this->assertEquals(50, $prev2Count);

        $sampleLog = MonitoringEquipmentLog::where('tag_number_id', $tagIds[0])
            ->where('period_code', $this->prev1['code'])
            ->first();
        $this->assertEquals('Medium', $sampleLog->status);
        $this->assertEquals('Sedang', $sampleLog->kondisi_peralatan);
    }

    // =========================================================================
    // UPDATE TESTS
    // =========================================================================

    public function test_update_fills_previous_periods_for_single_equipment(): void
    {
        $tagIds = $this->seedTagNumbers(5);
        $targetTagId = $tagIds[0];

        $oldPeriod = BusinessPeriod::previous(4);
        $this->createEquipment($tagIds);
        $this->createLogs($tagIds, $oldPeriod['code'], [
            'start' => $oldPeriod['start'],
            'end' => $oldPeriod['end'],
        ]);

        $equipment = MonitoringEquipment::where('tag_number_id', $targetTagId)->first();

        $this->assertNull(
            MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
                ->where('period_code', $this->prev1['code'])
                ->first()
        );

        $this->putJson('/api/monitoring_equipment/' . $equipment->id, [
            'tag_number_id' => $targetTagId,
            'status' => 'Breakdown',
            'kondisi_peralatan' => 'Rusak',
        ], $this->authHeaders())->assertStatus(200);

        $prev1Log = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev1['code'])
            ->first();
        $this->assertNotNull($prev1Log, 'previous(1) log should be created');

        $prev2Log = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev2['code'])
            ->first();
        $this->assertNotNull($prev2Log, 'previous(2) log should be created');
    }

    public function test_update_does_not_overwrite_existing_previous_period_logs(): void
    {
        $tagIds = $this->seedTagNumbers(3);
        $targetTagId = $tagIds[0];

        $this->createEquipment([$targetTagId]);
        $this->createLogs([$targetTagId], $this->prev1['code'], [
            'start' => $this->prev1['start'],
            'end' => $this->prev1['end'],
        ], ['status' => 'Low', 'kondisi_peralatan' => 'Original']);

        $originalPrev1Log = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev1['code'])
            ->first();

        $equipment = MonitoringEquipment::where('tag_number_id', $targetTagId)->first();

        $this->putJson('/api/monitoring_equipment/' . $equipment->id, [
            'tag_number_id' => $targetTagId,
            'status' => 'Breakdown',
            'kondisi_peralatan' => 'Berubah',
        ], $this->authHeaders())->assertStatus(200);

        $unchangedLog = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev1['code'])
            ->first();

        $this->assertEquals('Low', $unchangedLog->status);
        $this->assertEquals('Original', $unchangedLog->kondisi_peralatan);
    }

    // =========================================================================
    // DELETE TESTS
    // =========================================================================

    public function test_delete_only_removes_current_period_log(): void
    {
        $tagIds = $this->seedTagNumbers(3);
        $targetTagId = $tagIds[0];

        $this->createEquipment([$targetTagId]);
        $this->createLogs([$targetTagId], $this->prev1['code'], [
            'start' => $this->prev1['start'],
            'end' => $this->prev1['end'],
        ]);

        $this->createLogs([$targetTagId], $this->prev2['code'], [
            'start' => $this->prev2['start'],
            'end' => $this->prev2['end'],
        ]);

        $currentPeriod = BusinessPeriod::current();
        MonitoringEquipmentLog::create([
            'tag_number_id' => $targetTagId,
            'period_code' => $currentPeriod['code'],
            'period_start' => $currentPeriod['start'],
            'period_end' => $currentPeriod['end'],
            'status' => 'High',
        ]);

        $equipment = MonitoringEquipment::where('tag_number_id', $targetTagId)->first();
        $equipmentId = $equipment->id;

        $this->deleteJson('/api/monitoring_equipment/' . $equipmentId, [], $this->authHeaders())
            ->assertStatus(200);

        $currentLog = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->periodCode)
            ->first();
        $this->assertNull($currentLog, 'Current period log should be deleted');

        $prev1Log = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev1['code'])
            ->first();
        $this->assertNotNull($prev1Log, 'previous(1) log should still exist');

        $prev2Log = MonitoringEquipmentLog::where('tag_number_id', $targetTagId)
            ->where('period_code', $this->prev2['code'])
            ->first();
        $this->assertNotNull($prev2Log, 'previous(2) log should still exist');
    }
}
