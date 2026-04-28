<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Queue extends Model
{
    use SoftDeletes;
    protected $fillable = ['patient_id', 'polyclinic_id', 'doctor_id', 'queue_number', 'date', 'status', 'check_in_time', 'is_priority'];
    
    // Menambahkan field dinamis ke response JSON
    protected $appends = ['position_waiting'];

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
        if ($this->status !== 'waiting') {
            return 0;
        }

        $isPriority = $this->is_priority ?? false;

        return self::where('polyclinic_id', $this->polyclinic_id)
            ->where('date', $this->date)
            ->whereIn('status', ['waiting', 'examining'])
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

    public static function getAll()
    {
        return self::with(['patient', 'polyclinic', 'doctor.user'])->get();
    }

    public static function getById($id)
    {
        return self::with(['patient', 'polyclinic', 'doctor.user'])->findOrFail($id);
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