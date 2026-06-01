<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'user_id' => 'sometimes|required|exists:users,id|unique:patients,user_id,' . $id,
            'medical_record_number' => 'sometimes|nullable|string|max:50|unique:patients,medical_record_number,' . $id,
        ];
    }
}
