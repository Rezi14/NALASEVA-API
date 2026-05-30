<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $id = $this->route('doctor');
        $doctor = \App\Models\Doctor::findOrFail($id);

        return [
            'name'           => 'sometimes|required|string|max:255',
            'polyclinic_id'  => 'sometimes|required|integer|exists:polyclinics,id',
            'specialization' => 'sometimes|required|string|max:255',
            'license_number' => 'sometimes|required|string|max:255',
            'national_id'    => ['sometimes', 'required', 'string', 'digits:16', Rule::unique('users')->ignore($doctor->user_id)->whereNull('deleted_at')],
            'gender'         => 'sometimes|required|string|in:Laki-laki,Perempuan',
            'birth_date'     => 'sometimes|required|date_format:Y-m-d',
            'phone'          => 'sometimes|required|string|max:20',
            'address'        => 'sometimes|required|string|max:500',
        ];
    }
}
