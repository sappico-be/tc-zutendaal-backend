<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_group_id', 'location_id', 'lesson_date',
        'start_time', 'end_time', 'status', 'notes'
    ];

    protected $casts = [
        'lesson_date' => 'date',
    ];

    public function group()
    {
        return $this->belongsTo(LessonGroup::class, 'lesson_group_id');
    }

    public function location()
    {
        return $this->belongsTo(LessonLocation::class);
    }
}
