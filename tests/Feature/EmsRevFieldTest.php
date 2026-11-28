<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ems;
use App\Models\Tag_number;
use App\Models\Type;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
}
