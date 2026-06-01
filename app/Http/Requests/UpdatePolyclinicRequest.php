<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolyclinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'code' => ['sometimes', 'required', 'string', 'max:5', 'regex:/^[A-Z0-9]+$/', Rule::unique('polyclinics')->ignore($id)->whereNull('deleted_at')],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string'
        ];
    }
}
