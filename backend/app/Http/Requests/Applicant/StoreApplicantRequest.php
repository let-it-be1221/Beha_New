<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantRequest extends FormRequest
{
    public function authorize(): bool { return true; } // public self-signup

    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:191'],
            'email'        => ['required', 'email:rfc,dns', 'max:191'],
            'phone'        => ['required', 'string', 'max:32'],
            'national_id'  => ['nullable', 'string', 'max:64'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'education'    => ['nullable', 'array'],
            'education.*.institution' => ['nullable', 'string', 'max:191'],
            'education.*.degree'     => ['nullable', 'string', 'max:191'],
            'education.*.year'        => ['nullable', 'integer', 'min:1950', 'max:' . now()->year],
            'experience'   => ['nullable', 'array'],
            'experience.*.company'  => ['nullable', 'string', 'max:191'],
            'experience.*.role'     => ['nullable', 'string', 'max:191'],
            'experience.*.years'     => ['nullable', 'integer', 'min:0', 'max:60'],
            'references'   => ['nullable', 'array'],
            'references.*.name'  => ['nullable', 'string', 'max:191'],
            'references.*.phone' => ['nullable', 'string', 'max:32'],
            'references.*.email' => ['nullable', 'email:rfc,dns'],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms_accepted.accepted' => 'You must accept the terms to submit your application.',
        ];
    }
}
