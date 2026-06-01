<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'sometimes|required|integer|exists:doctors,id',
            'leave_date' => 'sometimes|required|date',
            'reason' => 'nullable|string',
        ];
    }
}
