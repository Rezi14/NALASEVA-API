<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('user');

        return [
            'name'        => 'sometimes|required|string|max:255',
            'email'       => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
            'password'    => 'sometimes|nullable|string|min:6',
            'phone'       => 'sometimes|required|string|max:20',
            'address'     => 'sometimes|required|string',
            'national_id' => ['sometimes', 'required', 'digits:16', Rule::unique('users')->ignore($id)->whereNull('deleted_at')],
            'gender'      => 'sometimes|required|string|in:Laki-laki,Perempuan',
            'birth_date'  => 'sometimes|required|date_format:Y-m-d',
        ];
    }
}
