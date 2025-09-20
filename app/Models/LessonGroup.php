<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_package_id', 'name', 'trainer_id', 'location_id',
        'level', 'max_participants', 'schedule_days',
        'default_start_time', 'default_end_time'
    ];

    protected $casts = [
        'schedule_days' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(LessonPackage::class, 'lesson_package_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function location()
    {
        return $this->belongsTo(LessonLocation::class);
    }

    public function registrations()
    {
        return $this->hasMany(LessonRegistration::class, 'assigned_group_id');
    }

    public function schedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }
}
