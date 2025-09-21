<?php
// app/Models/TrainerHourRegistration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainerHourRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_schedule_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'hourly_rate',
        'total_amount',
        'type',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($registration) {
            // Calculate hours if not set
            if (!$registration->hours && $registration->start_time && $registration->end_time) {
                $registration->hours = $registration->calculateHours();
            }
            
            // Calculate total amount
            if ($registration->hours && $registration->hourly_rate) {
                $registration->total_amount = $registration->hours * $registration->hourly_rate;
            }
        });
        
        static::updating(function ($registration) {
            // Recalculate hours if times changed
            if ($registration->isDirty(['start_time', 'end_time'])) {
                $registration->hours = $registration->calculateHours();
            }
            
            // Recalculate total amount
            if ($registration->isDirty(['hours', 'hourly_rate'])) {
                $registration->total_amount = $registration->hours * $registration->hourly_rate;
            }
        });
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lessonSchedule()
    {
        return $this->belongsTo(LessonSchedule::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateHours()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        
        $start = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time);
        $end = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);
        
        // If end time is before start time, assume it's the next day
        if ($end < $start) {
            $end->addDay();
        }
        
        return round($start->diffInMinutes($end) / 60, 2);
    }

    public function approve($adminId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);
    }

    public function reject($adminId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $reason,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForTrainer($query, $trainerId)
    {
        return $query->where('user_id', $trainerId);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
                     ->whereMonth('date', $month);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'lesson' => 'primary',
            'preparation' => 'info',
            'meeting' => 'warning',
            'tournament' => 'success',
            'other' => 'secondary',
            default => 'default'
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'lesson' => 'tabler-school',
            'preparation' => 'tabler-notebook',
            'meeting' => 'tabler-users',
            'tournament' => 'tabler-trophy',
            'other' => 'tabler-dots',
            default => 'tabler-clock'
        };
    }
}
