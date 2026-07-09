<?php

namespace App\Http\Requests;

class ImportMonitoringEquipmentRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'file' => [

                'required',

                'file',

                'mimes:xlsx,xls',

                'max:10240', // 10MB

            ]

        ];
    }
}