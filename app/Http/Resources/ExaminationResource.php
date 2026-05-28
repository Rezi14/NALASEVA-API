<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExaminationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_id' => $this->queue_id,
            'doctor_id' => $this->doctor_id,
            'complaint' => $this->complaint,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'queue' => new QueueResource($this->whenLoaded('queue')),
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
