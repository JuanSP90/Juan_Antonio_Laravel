<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    protected $fillable = [
        'provider',
        'amount',
        'currency',
        'request_data'
    ];

    protected $casts = [
        'request_data' => 'array'
    ];

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
} 