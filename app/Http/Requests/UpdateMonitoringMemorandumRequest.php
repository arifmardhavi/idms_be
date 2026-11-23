<?php

namespace App\Http\Requests;

use App\Support\MonitoringMemorandumRules;
use Illuminate\Validation\Validator;

class UpdateMonitoringMemorandumRequest extends BaseRequest
{
    /** WAJIB: FormRequest::authorize() default-nya false -> 403 tanpa ini. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return MonitoringMemorandumRules::rules(true);
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
