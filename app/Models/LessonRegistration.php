<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_package_id', 'user_id', 'available_days', 'preferred_partners',
        'remarks', 'level', 'assigned_group_id', 'status', 
        'payment_status', 'amount_paid'
    ];

    protected $casts = [
        'available_days' => 'array',
        'preferred_partners' => 'array',
    ];

    public function package()
    {
        return $this->belongsTo(LessonPackage::class, 'lesson_package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedGroup()
    {
        return $this->belongsTo(LessonGroup::class, 'assigned_group_id');
    }
}
