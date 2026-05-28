<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'password'    => 'required|string|min:6',
            'role'        => 'required|string|in:doctor,patient',
            'phone'       => 'required|string|max:20',
            'address'     => 'required|string',
            'national_id' => ['required', 'digits:16', Rule::unique('users')->whereNull('deleted_at')],
            'gender'      => 'required|string|in:Laki-laki,Perempuan',
            'birth_date'  => 'required|date_format:Y-m-d',
        ];
    }
}
