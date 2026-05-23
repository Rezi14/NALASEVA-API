<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicHoliday extends Model
{
    use SoftDeletes;

    protected $fillable = ['holiday_date', 'description'];
}
