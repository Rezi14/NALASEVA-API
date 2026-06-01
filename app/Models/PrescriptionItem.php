<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrescriptionItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['examination_id', 'medicine_id', 'quantity', 'instruction', 'price'];

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
