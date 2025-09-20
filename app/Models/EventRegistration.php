<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'user_id', 'status', 'amount_paid', 'payment_status',
        'payment_method', 'payment_reference', 'paid_at', 'notes', 'additional_info'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'additional_info' => 'array',
        'amount_paid' => 'decimal:2',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function confirm()
    {
        $this->update(['status' => 'confirmed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);
    }
}
