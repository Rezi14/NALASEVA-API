<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id|unique:patients,user_id',
            'medical_record_number' => 'nullable|string|max:50|unique:patients,medical_record_number',
        ];
    }
}
