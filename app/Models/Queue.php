<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use SoftDeletes;
    protected $fillable = ['patient_id', 'polyclinic_id', 'doctor_id', 'queue_number', 'date', 'status', 'check_in_time', 'called_time', 'is_priority'];
    
    // Menambahkan field dinamis ke response JSON
    protected $appends = ['position_waiting', 'avg_waiting_time', 'estimated_service_time'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Accessor untuk menghitung sisa antrean di depan pasien dengan perhitungan prioritas
    public function getPositionWaitingAttribute()
    {
        if (!in_array($this->status, ['booked', 'waiting'])) {
            return 0;
        }

        $isPriority = $this->is_priority ?? false;

        $statuses = $this->status === 'booked' 
            ? ['booked', 'waiting', 'examining']
            : ['waiting', 'examining'];

        return self::where('polyclinic_id', $this->polyclinic_id)
            ->where('date', $this->date)
            ->whereIn('status', $statuses)
            ->where('id', '!=', $this->id)
            ->where(function ($query) use ($isPriority) {
                if ($isPriority) {
                    // Pasien prioritas hanya menunggu sesama prioritas yang datang lebih dulu
                    $query->where('is_priority', true)
                          ->where('id', '<', $this->id);
                } else {
                    // Pasien reguler menunggu semua pasien prioritas + pasien reguler yang datang lebih dulu
                    $query->where('is_priority', true)
                          ->orWhere(function ($q) {
                              $q->where(function($q2) {
                                  $q2->whereNull('is_priority')->orWhere('is_priority', false);
                              })->where('id', '<', $this->id);
                          });
                }
            })
            ->count();
    }

    // Accessor untuk menghitung rata-rata waktu pemeriksaan riil poliklinik hari ini (Adaptive Waiting Time)
    public function getAvgWaitingTimeAttribute()
    {
        $polyclinicId = $this->polyclinic_id;
        $today = $this->date;

        // Ambil 3 pemeriksaan terakhir selesai hari ini di poliklinik yang sama
        $completedExams = \App\Models\Examination::whereHas('queue', function($query) use ($polyclinicId, $today) {
                $query->where('polyclinic_id', $polyclinicId)
                      ->where('date', $today);
            })
            ->with('queue')
            ->latest()
            ->limit(3)
            ->get();

        if ($completedExams->isEmpty()) {
            return 15; // default fallback 15 menit
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($completedExams as $exam) {
            if (!$exam->queue) {
                continue;
            }
            
            $start = $exam->queue->called_time ? \Carbon\Carbon::parse($exam->queue->called_time) : null;
            if (!$start && $exam->queue->check_in_time) {
                $start = \Carbon\Carbon::parse($exam->queue->check_in_time);
            }
            if (!$start) {
                $start = \Carbon\Carbon::parse($exam->queue->created_at);
            }
            
            $end = \Carbon\Carbon::parse($exam->created_at);
            
            $diff = $start->diffInMinutes($end);
            
            // Fallback jika ada pengujian langsung pada created_at dan updated_at pemeriksaan
            if ($diff <= 0) {
                $examStart = \Carbon\Carbon::parse($exam->created_at);
                $examEnd = \Carbon\Carbon::parse($exam->updated_at);
                $diff = $examStart->diffInMinutes($examEnd);
            }

            // Batasi durasi pemeriksaan masuk akal antara 1 menit hingga 120 menit
            $totalMinutes += max(1, min($diff, 120));
            $count++;
        }

        return $count > 0 ? (int)round($totalMinutes / $count) : 15;
    }

    public function getEstimatedServiceTimeAttribute()
    {
        if (!in_array($this->status, ['booked', 'waiting'])) {
            return null;
        }

        $peopleAhead = $this->position_waiting;
        $fixedMinutes = 15;

        $dayOfWeekEnglish = \Carbon\Carbon::parse($this->date)->format('l');
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $dayName = $days[$dayOfWeekEnglish] ?? '';

        $schedule = \App\Models\DoctorSchedule::where('doctor_id', $this->doctor_id)
            ->where('day_of_week', $dayName)
            ->first();

        $baseTime = now();
        if ($schedule) {
            $scheduleStart = \Carbon\Carbon::parse($this->date . ' ' . $schedule->start_time);
            if ($baseTime->lt($scheduleStart)) {
                $baseTime = $scheduleStart;
            }
        }

        return $baseTime->addMinutes($peopleAhead * $fixedMinutes)->format('H:i');
    }

    public static function getAll()
    {
        return self::with(['patient.user', 'polyclinic', 'doctor.user'])->get();
    }

    public static function getById($id)
    {
        return self::with(['patient.user', 'polyclinic', 'doctor.user'])->findOrFail($id);
    }

    public static function storeData($data)
    {
        return self::create($data);
    }

    public static function updateData($id, $data)
    {
        $queue = self::findOrFail($id);
        $queue->update($data);
        return $queue;
    }

    public static function softDeleteData($id)
    {
        return self::findOrFail($id)->delete();
    }

    public static function restoreData($id)
    {
        $queue = self::onlyTrashed()->findOrFail($id);
        return $queue->restore();
    }
}