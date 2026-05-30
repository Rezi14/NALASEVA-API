<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'polyclinic_id'  => 'required|integer|exists:polyclinics,id',
            'specialization' => 'required|string|max:255',
            'license_number' => 'required|string|max:255',
            'national_id'    => 'required|string|digits:16|unique:users,national_id',
            'gender'         => 'required|string|in:Laki-laki,Perempuan',
            'birth_date'     => 'required|date_format:Y-m-d',
            'phone'          => 'required|string|max:20',
            'address'        => 'required|string|max:500',
        ];
    }
}
