<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'content', 'type', 'location',
        'featured_image', 'start_date', 'end_date', 'registration_deadline',
        'max_participants', 'min_participants', 'price_members', 'price_non_members',
        'members_only', 'status', 'settings', 'created_by'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'settings' => 'array',
        'members_only' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function confirmedRegistrations()
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function getAvailableSpotsAttribute()
    {
        if (!$this->max_participants) {
            return null;
        }
        
        return $this->max_participants - $this->confirmedRegistrations()->count();
    }

    public function canRegister()
    {
        if ($this->status !== 'published') {
            return false;
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }

        if ($this->available_spots !== null && $this->available_spots <= 0) {
            return false;
        }

        return true;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'published')
                     ->where('start_date', '>', now())
                     ->orderBy('start_date');
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now())
                     ->orderBy('start_date', 'desc');
    }
}
