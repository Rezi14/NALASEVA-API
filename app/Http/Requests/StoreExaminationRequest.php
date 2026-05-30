<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'doctor';
    }

    public function rules(): array
    {
        return [
            'queue_id'  => 'required|integer|exists:queues,id',
            'complaint' => 'required|string',
            'diagnosis' => 'required|string',
            'treatment' => 'required|string',
        ];
    }
}
