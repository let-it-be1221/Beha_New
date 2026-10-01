<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'       => ['required', 'string', 'max:191'],
            'email'           => ['nullable', 'email:rfc,dns', 'max:191'],
            'phone'           => ['required', 'string', 'max:32'],
            'national_id'     => ['nullable', 'string', 'max:64'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],
            'customer_source' => ['nullable', 'string', 'max:64'],
            'notes'           => ['nullable', 'string', 'max:5000'],
            'metadata'        => ['nullable', 'array'],
        ];
    }
}
