<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'generation_id'  => ['required', 'exists:generations,id'],
            'name'           => ['required', 'string', 'max:120'],
            'branch_number'  => ['required', 'integer', 'min:1'],
            'leader_user_id' => ['nullable', 'exists:users,id'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'is_active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'generation_id.required' => 'A generation must be selected.',
        ];
    }
}

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id ?? $this->route('id');

        return [
            'generation_id'  => ['sometimes', 'exists:generations,id'],
            'name'           => ['sometimes', 'string', 'max:120'],
            'branch_number' => ['sometimes', 'integer', 'min:1'],
            'leader_user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
