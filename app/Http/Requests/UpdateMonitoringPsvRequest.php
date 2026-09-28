<?php

namespace App\Http\Requests;

use App\Models\MonitoringPsv;

class UpdateMonitoringPsvRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi `status_redundant` SEBELUM validasi.
     *
     * Tanpa langkah ini, aturan `in:Redundant,No` bisa ditembus dengan
     * variations kecil seperti `redundant`, `Redundant ` (ada spasi), atau
     * `No`. Nilai-nilai itu lolos validasi tetapi TIDAK akan terhitung di
     * `MonitoringPsvDashboardService::statusRedundant()` yang memakai
     * kesamaan string persis — barisnya hilang dari rekap tanpa error, lalu
     * `redundant + not_redundant + unfilled` tidak lagi sama dengan `total`.
     *
     * Yang dilakukan di sini:
     *   "Redundant" / "redundant" / " REDUNDANT "  -> "Redundant"
     *   "No" / "no" / " NO "                       -> "No"
     *   ""  (string kosong)                        -> null  (artinya dikosongkan)
     *   null                                      -> null  (dibiarkan)
     *   "not redundant", "-", angka, array, dll.  -> dibiarkan, rule in: yang menolak
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('status_redundant')) {
            return;
        }

        $value = $this->input('status_redundant');

        if ($value === null) {
            return;
        }

        // Hanya string yang boleh dinormalisasi. Bentuk lain dibiarkan apa
        // adanya supaya rule `string` / `in` yang menolak, bukan kode ini.
        if (! is_string($value)) {
            return;
        }

        $trimmed = trim($value);

        /*
         * String kosong diperlakukan sama dengan null. Kalau tidak, pengguna
         * yang bermaksud mengosongkan kolom akan berakhir dengan tersimpan ""
         * yang bukan NULL — dan "" tidak akan terhitung sebagai `unfilled`.
         */
        if ($trimmed === '') {
            $this->merge(['status_redundant' => null]);

            return;
        }

        foreach (MonitoringPsv::STATUS_REDUNDANT_OPTIONS as $canonical) {
            if (mb_strtolower($trimmed) === mb_strtolower($canonical)) {
                $this->merge(['status_redundant' => $canonical]);

                return;
            }
        }
    }

    public function rules(): array
    {
        return [

            /**
             * Status Redundant — hanya 'Redundant' atau 'No'. Input dicocokkan
             * case-insensitive lalu dinormalisasi ke bentuk kanonik oleh
             * prepareForValidation(), jadi nilai yang tersimpan selalu cocok
             * dengan rekap dashboard.
             */
            'status_redundant' => 'nullable|string|in:' . implode(',', MonitoringPsv::STATUS_REDUNDANT_OPTIONS),

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
