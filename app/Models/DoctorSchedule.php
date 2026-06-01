<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Model
{
    use SoftDeletes;
    protected $fillable = ['doctor_id', 'day_of_week', 'start_time', 'end_time'];

    // Mapping nama hari dari Database Enum (English) ke Application/JSON (Indonesia)
    private static $dayMap = [
        'monday'    => 'Senin',
        'tuesday'   => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday'  => 'Kamis',
        'friday'    => 'Jumat',
        'saturday'  => 'Sabtu',
        'sunday'    => 'Minggu',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Accessor: Database (English) -> App/Flutter (Indonesian)
     */
    public function getDayOfWeekAttribute($value)
    {
        $valueLower = strtolower($value);
        return self::$dayMap[$valueLower] ?? $value;
    }

    /**
     * Mutator: App/Seeder/API Input (Indonesian/English) -> Database Enum (English)
     */
    public function setDayOfWeekAttribute($value)
    {
        $valueLower = strtolower($value);
        $flipped = array_flip(self::$dayMap);
        
        if (isset($flipped[$value])) {
            $this->attributes['day_of_week'] = $flipped[$value];
        } else {
            if (in_array($valueLower, array_keys(self::$dayMap))) {
                $this->attributes['day_of_week'] = $valueLower;
            } else {
                $this->attributes['day_of_week'] = $value;
            }
        }
    }
}
