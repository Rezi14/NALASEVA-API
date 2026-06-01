<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolyclinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/', Rule::unique('polyclinics')->whereNull('deleted_at')],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ];
    }
}
