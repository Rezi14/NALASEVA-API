<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_id' => $this->queue_id,
            'examination_id' => $this->examination_id,
            'transaction_number' => $this->transaction_number,
            'registration_fee' => $this->registration_fee,
            'medicine_fee' => $this->medicine_fee,
            'total_amount' => $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_proof' => $this->payment_proof,
            'payment_proof_url' => $this->payment_proof ? Storage::disk('public')->url($this->payment_proof) : null,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'dispensed_at' => $this->dispensed_at,
            'queue' => new QueueResource($this->whenLoaded('queue')),
            'examination' => new ExaminationResource($this->whenLoaded('examination')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
