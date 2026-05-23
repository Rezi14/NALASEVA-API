<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorLeave extends Model
{
    use SoftDeletes;

    protected $fillable = ['doctor_id', 'leave_date', 'reason'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
