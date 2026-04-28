<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id', 'polyclinic_id', 'specialization'];

    protected static function booted()
    {
        static::deleted(function ($doctor) {
            $doctor->schedules()->delete();
        });

        static::restored(function ($doctor) {
            $doctor->schedules()->restore();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function polyclinic()
    {
        return $this->belongsTo(Polyclinic::class);
    }
    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public static function getAll()
    {
        return self::with(['user', 'polyclinic', 'schedules'])->get();
    }

    public static function getById($id)
    {
        return self::with(['user', 'polyclinic', 'schedules'])->findOrFail($id);
    }

    public static function storeData($data)
    {
        return self::create($data);
    }

    public static function updateData($id, $data)
    {
        $doctor = self::findOrFail($id);
        $doctor->update($data);
        return $doctor;
    }

    public static function softDeleteData($id)
    {
        return self::findOrFail($id)->delete();
    }

    public static function restoreData($id)
    {
        $doctor = self::onlyTrashed()->findOrFail($id);
        return $doctor->restore();
    }
}
