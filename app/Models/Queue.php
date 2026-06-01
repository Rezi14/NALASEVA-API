<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use SoftDeletes;
    protected $fillable = ['patient_id', 'polyclinic_id', 'doctor_id', 'doctor_schedule_id', 'queue_number', 'date', 'status', 'check_in_time', 'called_time', 'is_priority', 'reason', 'recall_count', 'estimated_service_time'];
    
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
    public function doctorSchedule()
    {
        return $this->belongsTo(DoctorSchedule::class);
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

        $thisTime = $this->check_in_time ?? $this->created_at;

        return self::where('polyclinic_id', $this->polyclinic_id)
            ->where('date', $this->date)
            ->whereIn('status', $statuses)
            ->where('id', '!=', $this->id)
            ->where(function ($query) use ($isPriority, $thisTime) {
                if ($isPriority) {
                    // Pasien prioritas hanya menunggu sesama prioritas yang datang lebih dulu
                    // DAN tetap menunggu siapa pun (reguler maupun prioritas) yang sedang diperiksa (examining)
                    $query->where(function($q) use ($thisTime) {
                        $q->where('is_priority', true)
                          ->where(\Illuminate\Support\Facades\DB::raw('COALESCE(check_in_time, created_at)'), '<', $thisTime);
                    })->orWhere('status', 'examining');
                } else {
                    // Pasien reguler menunggu semua pasien prioritas + pasien reguler yang datang lebih dulu
                    $query->where('is_priority', true)
                          ->orWhere(function ($q) use ($thisTime) {
                              $q->where(function($q2) {
                                  $q2->whereNull('is_priority')->orWhere('is_priority', false);
                                })->where(\Illuminate\Support\Facades\DB::raw('COALESCE(check_in_time, created_at)'), '<', $thisTime);
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

    public static function calculateEstimatedServiceTime($queue)
    {
        if (!in_array($queue->status, ['booked', 'waiting'])) {
            return null;
        }

        $peopleAhead = $queue->position_waiting;
        $fixedMinutes = 15;

        $dayOfWeekEnglish = \Carbon\Carbon::parse($queue->date)->format('l');
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $dayName = $days[$dayOfWeekEnglish] ?? '';

        // Gunakan jadwal spesifik yang terikat pada antrean ini
        $schedule = null;
        if ($queue->doctor_schedule_id) {
            $schedule = \App\Models\DoctorSchedule::find($queue->doctor_schedule_id);
        }
        // Fallback ke pencarian berdasarkan hari jika doctor_schedule_id tidak ada
        if (!$schedule) {
            $schedule = \App\Models\DoctorSchedule::where('doctor_id', $queue->doctor_id)
                ->where('day_of_week', $dayName)
                ->first();
        }

        if (!$schedule) {
            return null;
        }

        $baseTime = now();
        $scheduleStart = \Carbon\Carbon::parse($queue->date . ' ' . $schedule->start_time);
        if ($baseTime->lt($scheduleStart)) {
            $baseTime = $scheduleStart;
        }

        return $baseTime->addMinutes($peopleAhead * $fixedMinutes)->format('H:i');
    }

    public static function recalculateEstimatedTimes(int $polyclinicId, string $date): void
    {
        $activeQueues = self::where('polyclinic_id', $polyclinicId)
            ->where('date', $date)
            ->whereIn('status', ['booked', 'waiting'])
            ->orderByRaw('is_priority DESC, COALESCE(check_in_time, created_at) ASC, id ASC')
            ->get();

        foreach ($activeQueues as $queue) {
            $newEst = self::calculateEstimatedServiceTime($queue);
            if ($newEst !== null) {
                $queue->update(['estimated_service_time' => $newEst]);
            }
        }
    }
}