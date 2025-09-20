<?php
// app/Models/LessonAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_schedule_id',
        'user_id',
        'status',
        'notes',
        'checked_at',
        'checked_by'
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(LessonSchedule::class, 'lesson_schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkedByUser()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }
}
