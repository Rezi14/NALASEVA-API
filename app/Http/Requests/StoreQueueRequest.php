<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'         => ['required', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'polyclinic_id'      => ['required', 'integer', Rule::exists('polyclinics', 'id')->whereNull('deleted_at')],
            'doctor_id'          => ['required', 'integer', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'doctor_schedule_id' => ['required', 'integer', Rule::exists('doctor_schedules', 'id')->whereNull('deleted_at')],
            'date'               => 'required|date',
            'is_priority'        => 'sometimes|boolean',
        ];
    }
}
