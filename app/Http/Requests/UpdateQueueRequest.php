<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, ['admin', 'doctor']);
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|in:booked,waiting,examining,completed,cancelled',
        ];
    }
}
