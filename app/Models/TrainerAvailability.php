<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'lesson_package_id', 'day_of_week',
        'available_from', 'available_until', 'is_available'
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package()
    {
        return $this->belongsTo(LessonPackage::class, 'lesson_package_id');
    }
}
