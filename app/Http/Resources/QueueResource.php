<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'polyclinic_id' => $this->polyclinic_id,
            'doctor_id' => $this->doctor_id,
            'doctor_schedule_id' => $this->doctor_schedule_id,
            'queue_number' => $this->queue_number,
            'date' => $this->date,
            'status' => $this->status,
            'check_in_time' => $this->check_in_time,
            'called_time' => $this->called_time,
            'is_priority' => $this->is_priority,
            'reason' => $this->reason,
            'recall_count' => $this->recall_count,
            'position_waiting' => $this->position_waiting,
            'avg_waiting_time' => $this->avg_waiting_time,
            'estimated_service_time' => $this->estimated_service_time,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'polyclinic' => new PolyclinicResource($this->whenLoaded('polyclinic')),
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            'doctor_schedule' => $this->doctorSchedule,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
