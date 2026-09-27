<?php

namespace App\Http\Requests;

class UpdateMonitoringPsvRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /**
             * Status Redundant / No — inputan string bebas.
             */
            'status_redundant' => 'nullable|string|max:50',

            'pid_no' => 'nullable|string|max:50',

            /**
             * Kategori — pilihan CSO / CSC.
             */
            'kategori' => 'nullable|string|in:CSO,CSC',

            'keterangan' => 'nullable|string|max:1000',

            /**
             * Tag Number & Masa Berlaku TIDAK bisa di-update dari sini —
             * keduanya read-only, bersumber dari COI.
             */
        ];
    }
}
