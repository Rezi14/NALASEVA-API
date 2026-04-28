<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use SoftDeletes;
    protected $fillable = ['doctor_id', 'day_of_week', 'start_time', 'end_time'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public static function getAll()
    {
        return self::with(['doctor'])->get();
    }

    public static function getById($id)
    {
        return self::with(['doctor'])->findOrFail($id);
    }

    public static function storeData($data)
    {
        return self::create($data);
    }

    public static function updateData($id, $data)
    {
        $schedule = self::findOrFail($id);
        $schedule->update($data);
        return $schedule;
    }

    public static function softDeleteData($id)
    {
        return self::findOrFail($id)->delete();
    }

    public static function restoreData($id)
    {
        $schedule = self::onlyTrashed()->findOrFail($id);
        return $schedule->restore();
    }
}
