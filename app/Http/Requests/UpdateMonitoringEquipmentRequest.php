<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitoringEquipmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'tag_number_id' => [

                'required',

                'exists:tag_numbers,id',

                Rule::unique('monitoring_equipment')
                    ->ignore($this->route('monitoring_equipment')),

            ],

            'status' => 'nullable|in:0,1,2,3', // 0: High, 1: Medium, 2: Low, 3: Breakdown

            'jenis_kerusakan' => 'nullable|string|max:255',

            'penyebab' => 'nullable|string|max:255',

            'penanganan_sementara' => 'nullable|string|max:255',

            'perbaikan_permanen' => 'nullable|string|max:255',

            'progress_perbaikan_permanen' => 'nullable|string|max:255',

            'kendala_perbaikan' => 'nullable|string|max:255',

            'estimasi_perbaikan' => 'nullable|integer|min:0',

            'target' => 'nullable|date',

        ];
    }
}