<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'    => 'required|integer|exists:patients,id',
            'polyclinic_id' => 'required|integer|exists:polyclinics,id',
            'doctor_id'     => 'required|integer|exists:doctors,id',
            'date'          => 'required|date',
            'is_priority'   => 'sometimes|boolean',
        ];
    }
}
