<?php

namespace App\Http\Requests;

use App\Support\MonitoringMemorandumRules;
use Illuminate\Validation\Validator;

class StoreMonitoringMemorandumRequest extends BaseRequest
{
    /** WAJIB: FormRequest::authorize() default-nya false -> 403 tanpa ini. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // isi rules() dipecah ke MonitoringMemorandumRules agar bisa dipakai juga di UpdateMonitoringMemorandumRequest.
        // isi rules() ini merupakan rules dari tipe data yang dikirimkan FE.
        return MonitoringMemorandumRules::rules(false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(MonitoringMemorandumRules::normalize($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        MonitoringMemorandumRules::validateTagIds($validator);
    }
}
