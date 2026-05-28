<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'polyclinic_id' => $this->polyclinic_id,
            'specialization' => $this->specialization,
            'license_number' => $this->license_number,
            'is_online' => $this->is_online,
            'user' => new UserResource($this->whenLoaded('user')),
            'polyclinic' => new PolyclinicResource($this->whenLoaded('polyclinic')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
