<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'holiday_date' => 'sometimes|required|date|unique:clinic_holidays,holiday_date,' . $id,
            'description' => 'nullable|string',
        ];
    }
}
