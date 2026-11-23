<?php

namespace App\Support;

use App\Models\Tag_number;
use Illuminate\Validation\Validator;

class MonitoringMemorandumRules
{
    /** @return array<string, string|array<int, string>> */
    public static function rules(bool $update = false): array
    {
        $p = fn (string $rule) => $update ? 'sometimes|' . $rule : $rule;

        return [
            'tanggal_issue'     => $p('required|date|before_or_equal:today'),
            'issue_operasional' => $p('required|string|max:255'),
            // WAJIB array: Laravel memecah rule string pada "|", sehingga
            // karakter "|" di dalam regex akan merusak rule bila ditulis string.
            'tag_number_ids'    => array_merge(
                $update ? ['sometimes'] : [],
                ['nullable', 'string', 'max:255', 'regex:/^(all|\d+(,\d+)*)$/i']
            ),
            'nota_dinas'        => $p('nullable|integer|exists:historical_memorandum,id'),
            'no_notif'          => $p('nullable|integer|min:0'),
            'status_notif'      => $p('nullable|string|in:OSNO,NOPR'),
            'no_wo'             => $p('nullable|integer|min:0'),
            'status_wo'         => $p('nullable|string|in:50,60'),
            'no_po'             => $p('nullable|string|max:150'),
            'status'            => $p('nullable|string|in:open,close'),
            'keterangan'        => $p('nullable|string|max:5000'),
        ];
    }

    /** String kosong -> null, hanya untuk key yang memang dikirim. */
    public static function normalize(array $data): array
    {
        $nullable = [
            'tag_number_ids', 'no_po', 'status_notif', 'status_wo',
            'status', 'keterangan',
        ];

        foreach ($nullable as $key) {
            if (array_key_exists($key, $data)
                && is_string($data[$key])
                && trim($data[$key]) === ''
            ) {
                $data[$key] = null;
            }
        }

        if (array_key_exists('tag_number_ids', $data)) {
            $data['tag_number_ids'] = is_string($data['tag_number_ids'])
                ? TagNumberIds::normalize($data['tag_number_ids'])
                : $data['tag_number_ids'];
        }

        return $data;
    }

    public static function validateTagIds(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('tag_number_ids')) {
                return;
            }

            $value = $validator->getData()['tag_number_ids'] ?? null;

            if ($value === null || TagNumberIds::isAll($value)) {
                return;
            }

            $ids = TagNumberIds::parse($value);

            if ($ids === []) {
                return;
            }

            $found = Tag_number::whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = array_diff($ids, $found);

            if ($missing !== []) {
                $validator->errors()->add(
                    'tag_number_ids',
                    'Tag Number tidak ditemukan: ' . implode(', ', $missing)
                );
            }
        });
    }
}
