<?php

namespace App\Http\Requests\Applicant;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:191'],
            'email'        => ['required', 'email:rfc,dns', 'max:191'],
            'phone'        => ['required', 'string', 'max:32'],
            'national_id'  => ['nullable', 'string', 'max:64'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'education'    => ['nullable', 'array'],
            'experience'   => ['nullable', 'array'],
            'references'   => ['nullable', 'array'],
            'profile_photo_path' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
