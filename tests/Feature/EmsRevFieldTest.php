<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ems;
use App\Models\Tag_number;
use App\Models\Type;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmsRevFieldTest extends TestCase
{
    use DatabaseTransactions;

    private function tagNumber(): Tag_number
    {
        $suffix = bin2hex(random_bytes(5));

        $category = Category::create(['category_name' => 'ems rev test '.$suffix]);
        $type = Type::create([
            'type_name' => 'ems rev test '.$suffix,
            'category_id' => $category->id,
        ]);
        $unit = Unit::create(['unit_name' => 'ems rev test '.$suffix]);

        return Tag_number::create([
            'unit_id' => $unit->id,
            'type_id' => $type->id,
            'tag_number' => 'EMS-REV-TEST-'.$suffix,
        ]);
    }

    public function test_new_ems_row_gets_rev_default_zero(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-001',
            'tanggal' => '2026-11-28',
        ]);

        $this->assertSame('0', $ems->fresh()->rev);
    }

    public function test_rev_can_be_set_to_a_string(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-002',
            'tanggal' => '2026-11-28',
            'rev' => '3',
        ]);

        $this->assertSame('3', $ems->fresh()->rev);
    }

    /** @return array<string, string> */
    private function authHeaders(): array
    {
        $suffix = bin2hex(random_bytes(5));

        $user = User::create([
            'fullname' => 'EMS rev test',
            'email' => 'ems_rev_'.$suffix.'@example.test',
            'username' => 'ems_rev_'.$suffix,
            'password' => 'secret123',
            'level_user' => 1,
            'status' => 1,
        ]);

        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)];
    }

    public function test_store_endpoint_defaults_rev_to_zero(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-003',
            'tanggal' => '2026-11-28',
        ]);

        $response->assertCreated()->assertJsonPath('data.rev', '0');
    }

    public function test_store_endpoint_accepts_rev_string(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-004',
            'tanggal' => '2026-11-28',
            'rev' => '7',
        ]);

        $response->assertCreated()->assertJsonPath('data.rev', '7');
    }

    public function test_update_endpoint_keeps_rev_when_not_provided(): void
    {
        $ems = Ems::create([
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-005',
            'tanggal' => '2026-11-28',
            'rev' => '9',
        ]);

        $response = $this->withHeaders($this->authHeaders())->putJson('/api/ems/'.$ems->id, [
            'no_ems' => 'EMS-REV-005-UPDATED',
        ]);

        $response->assertOk()->assertJsonPath('data.rev', '9');
    }

    public function test_store_endpoint_rejects_null_rev(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/ems', [
            'tag_number_id' => $this->tagNumber()->id,
            'no_ems' => 'EMS-REV-006',
            'tanggal' => '2026-11-28',
            'rev' => null,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('rev');
    }
}
