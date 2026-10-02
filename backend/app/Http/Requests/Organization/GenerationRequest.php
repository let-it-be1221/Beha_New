<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:120'],
            'generation_number' => ['required', 'integer', 'min:1', 'unique:generations,generation_number'],
            'leader_user_id'    => ['nullable', 'exists:users,id'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'is_active'         => ['boolean'],
        ];
    }
}

class UpdateGenerationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $generationId = $this->route('generation')?->id ?? $this->route('id');

        return [
            'name'              => ['sometimes', 'string', 'max:120'],
            'generation_number' => ['sometimes', 'integer', 'min:1', Rule::unique('generations', 'generation_number')->ignore($generationId)],
            'leader_user_id'    => ['sometimes', 'nullable', 'exists:users,id'],
            'description'       => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}
