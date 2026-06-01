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
            'prescription_items' => 'nullable|array',
            'prescription_items.*.medicine_id' => 'required|integer|exists:medicines,id',
            'prescription_items.*.quantity' => 'required|integer|min:1',
            'prescription_items.*.instruction' => 'required|string',
        ];
    }
}
