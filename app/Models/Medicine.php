<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'stock', 'unit', 'price'];

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
