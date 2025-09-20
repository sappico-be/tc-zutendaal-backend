<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'total_lessons', 'start_date', 'end_date',
        'registration_deadline', 'price_members', 'price_non_members',
        'status', 'min_participants', 'max_participants', 'available_days'
    ];

    protected $casts = [
        'available_days' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
    ];

    public function registrations()
    {
        return $this->hasMany(LessonRegistration::class);
    }

    public function groups()
    {
        return $this->hasMany(LessonGroup::class);
    }

    public function trainerAvailabilities()
    {
        return $this->hasMany(TrainerAvailability::class);
    }
}
