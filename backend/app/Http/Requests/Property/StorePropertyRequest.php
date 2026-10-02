<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:191'],
            'property_type'  => ['required', 'string', 'max:64'],
            'country'        => ['nullable', 'string', 'max:64'],
            'region'         => ['nullable', 'string', 'max:120'],
            'city'           => ['nullable', 'string', 'max:120'],
            'address'        => ['nullable', 'string', 'max:255'],
            'gps_lat'        => ['nullable', 'numeric', 'between:-90,90'],
            'gps_lng'        => ['nullable', 'numeric', 'between:-180,180'],
            'size_sqm'       => ['nullable', 'numeric', 'min:0'],
            'number_of_units'=> ['nullable', 'integer', 'min:1'],
            'bedrooms'       => ['nullable', 'integer', 'min:0'],
            'bathrooms'      => ['nullable', 'integer', 'min:0'],
            'floor'          => ['nullable', 'integer', 'min:0'],
            'building_info'  => ['nullable', 'string', 'max:191'],
            'developer'      => ['nullable', 'string', 'max:191'],
            'ownership_type' => ['nullable', 'string', 'max:48'],
            'price'          => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:10000'],
            'amenities'      => ['nullable', 'array'],
            'amenities.*'    => ['string', 'max:64'],
        ];
    }
}
