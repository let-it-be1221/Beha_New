<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'branch_id'      => ['required', 'exists:branches,id'],
            'name'           => ['required', 'string', 'max:120'],
            'team_number'    => ['required', 'integer', 'min:1'],
            'leader_user_id' => ['nullable', 'exists:users,id'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'is_active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'A branch must be selected.',
        ];
    }
}

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'branch_id'      => ['sometimes', 'exists:branches,id'],
            'name'           => ['sometimes', 'string', 'max:120'],
            'team_number'    => ['sometimes', 'integer', 'min:1'],
            'leader_user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
