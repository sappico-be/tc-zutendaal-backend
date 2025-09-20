<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'payable_type', 'payable_id', 'user_id',
        'amount', 'currency', 'status', 'payment_method', 'provider',
        'provider_payment_id', 'provider_response', 'description',
        'paid_at', 'refunded_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'provider_response' => 'array',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = 'TXN_' . strtoupper(uniqid());
            }
        });
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function refund()
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now()
        ]);
    }
}
