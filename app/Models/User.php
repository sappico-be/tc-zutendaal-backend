<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'mobile',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country',
        'member_number',
        'membership_type',
        'member_since',
        'membership_expires_at',
        'is_active',
        'tennis_level',
        'vta_number',
        'role',
        'can_book_courts',
        'receives_newsletter',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'avatar',
        'preferences',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'member_since' => 'date',
            'membership_expires_at' => 'date',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'can_book_courts' => 'boolean',
            'receives_newsletter' => 'boolean',
            'preferences' => 'array',
            'tennis_level' => 'decimal:1',
        ];
    }

    /**
     * Boot method voor model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            // Genereer automatisch een member number
            if (empty($user->member_number) && $user->membership_type !== 'non_member') {
                $user->member_number = self::generateMemberNumber();
            }
        });
    }

    /**
     * Genereer een uniek member number
     */
    public static function generateMemberNumber()
    {
        $year = date('Y');
        $lastMember = self::whereYear('created_at', $year)
                          ->where('member_number', 'like', "TC{$year}%")
                          ->orderBy('member_number', 'desc')
                          ->first();

        if ($lastMember) {
            $lastNumber = intval(substr($lastMember->member_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "TC{$year}{$newNumber}";
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        return $this->name;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->street,
            $this->house_number,
            $this->postal_code,
            $this->city,
        ]);
        
        return implode(' ', $parts);
    }

    /**
     * Check if membership is active
     */
    public function hasActiveMembership()
    {
        return $this->is_active 
            && $this->membership_type !== 'non_member'
            && ($this->membership_expires_at === null || $this->membership_expires_at->isFuture());
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is board member or higher
     */
    public function isBoardMember()
    {
        return in_array($this->role, ['admin', 'board_member']);
    }

    /**
     * Check if user is trainer
     */
    public function isTrainer()
    {
        return in_array($this->role, ['admin', 'board_member', 'trainer']);
    }

    /**
     * Scope for active members
     */
    public function scopeActiveMembers($query)
    {
        return $query->where('is_active', true)
                     ->where('membership_type', '!=', 'non_member')
                     ->where(function($q) {
                         $q->whereNull('membership_expires_at')
                           ->orWhere('membership_expires_at', '>', now());
                     });
    }

    /**
     * Scope for newsletter subscribers
     */
    public function scopeNewsletterSubscribers($query)
    {
        return $query->where('receives_newsletter', true)
                     ->where('is_active', true);
    }

    // ==========================================
    // RELATIES
    // ==========================================

    /**
     * News articles written by this user
     */
    public function newsArticles()
    {
        return $this->hasMany(NewsArticle::class, 'author_id');
    }

    /**
     * Events created by this user
     */
    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Event registrations
     */
    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Payments made by this user
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Events where user is registered
     */
    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_registrations')
                    ->withPivot('status', 'payment_status', 'paid_at', 'amount_paid')
                    ->withTimestamps();
    }

    /**
     * Get upcoming events for this user
     */
    public function upcomingEvents()
    {
        return $this->registeredEvents()
                    ->where('events.start_date', '>', now())
                    ->wherePivot('status', 'confirmed')
                    ->orderBy('events.start_date');
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the route key for the model (voor slug routing)
     */
    public function getRouteKeyName()
    {
        return 'member_number';
    }
}
