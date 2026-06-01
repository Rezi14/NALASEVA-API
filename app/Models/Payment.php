<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'queue_id',
        'examination_id',
        'transaction_number',
        'registration_fee',
        'medicine_fee',
        'total_amount',
        'payment_method',
        'payment_proof',
        'status',
        'paid_at',
        'dispensed_at',
    ];

    protected $casts = [
        'registration_fee' => 'decimal:2',
        'medicine_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'dispensed_at' => 'datetime',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }
}
