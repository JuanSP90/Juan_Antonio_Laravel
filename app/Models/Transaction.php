<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'payment_request_id',
        'amount',
        'currency',
        'status',
        'provider',
        'response_data'
    ];

    protected $casts = [
        'response_data' => 'array'
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }
} 