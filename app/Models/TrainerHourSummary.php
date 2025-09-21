<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerHourSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'total_hours',
        'lesson_hours',
        'preparation_hours',
        'meeting_hours',
        'tournament_hours',
        'other_hours',
        'total_amount',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'paid_at',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'date',
        'total_hours' => 'decimal:2',
        'lesson_hours' => 'decimal:2',
        'preparation_hours' => 'decimal:2',
        'meeting_hours' => 'decimal:2',
        'tournament_hours' => 'decimal:2',
        'other_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function hourRegistrations()
    {
        return $this->hasMany(TrainerHourRegistration::class, 'user_id', 'user_id')
                    ->whereYear('date', $this->year)
                    ->whereMonth('date', $this->month);
    }

    public function scopeForTrainer($query, $trainerId)
    {
        return $query->where('user_id', $trainerId);
    }

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'approved')
                     ->whereNull('paid_at');
    }

    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approve($adminId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid($reference = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $reference,
        ]);
    }

    public function getMonthNameAttribute()
    {
        return Carbon::create($this->year, $this->month)->format('F Y');
    }

    public function calculateTotals()
    {
        $registrations = $this->hourRegistrations()
                              ->where('status', 'approved')
                              ->get();
        
        $this->lesson_hours = $registrations->where('type', 'lesson')->sum('hours');
        $this->preparation_hours = $registrations->where('type', 'preparation')->sum('hours');
        $this->meeting_hours = $registrations->where('type', 'meeting')->sum('hours');
        $this->tournament_hours = $registrations->where('type', 'tournament')->sum('hours');
        $this->other_hours = $registrations->where('type', 'other')->sum('hours');
        
        $this->total_hours = $registrations->sum('hours');
        $this->total_amount = $registrations->sum('total_amount');
        
        $this->save();
    }
}
