<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hourly_rate',
        'preparation_rate',
        'tournament_rate',
        'start_date',
        'end_date',
        'contract_type',
        'max_hours_per_week',
        'max_hours_per_month',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
        'hourly_rate' => 'decimal:2',
        'preparation_rate' => 'decimal:2',
        'tournament_rate' => 'decimal:2',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where(function($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    public function getRateForType($type)
    {
        return match($type) {
            'lesson' => $this->hourly_rate,
            'preparation' => $this->preparation_rate ?? $this->hourly_rate,
            'tournament' => $this->tournament_rate ?? $this->hourly_rate,
            'meeting' => $this->hourly_rate,
            'other' => $this->hourly_rate,
            default => $this->hourly_rate
        };
    }

    public function isValidOn($date)
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($date < $this->start_date) {
            return false;
        }
        
        if ($this->end_date && $date > $this->end_date) {
            return false;
        }
        
        return true;
    }
}
