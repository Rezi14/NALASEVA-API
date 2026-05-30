<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'doctor';
    }

    public function rules(): array
    {
        return [
            'queue_id'  => 'sometimes|required|integer|exists:queues,id',
            'doctor_id' => 'sometimes|required|integer|exists:doctors,id',
            'complaint' => 'sometimes|required|string',
            'diagnosis' => 'sometimes|required|string',
            'treatment' => 'sometimes|required|string',
        ];
    }
}
