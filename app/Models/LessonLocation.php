<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'capacity', 'is_active', 'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function groups()
    {
        return $this->hasMany(LessonGroup::class, 'location_id');
    }

    public function schedules()
    {
        return $this->hasMany(LessonSchedule::class, 'location_id');
    }
}
