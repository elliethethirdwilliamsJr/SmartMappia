<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'national_id',
        'password',
        'role',
        'profile_photo',
        'rating',
        'total_ratings',
        'email_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'rating' => 'decimal:2',
        ];
    }

    // Relationships
    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function ridesAsCustomer()
    {
        return $this->hasMany(Ride::class, 'customer_id');
    }

    public function ridesAsDriver()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helpers
    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' || $this->role === 'user';
    }
    
    public function isUser(): bool
    {
        return $this->role === 'user';
    }
    
    public function isRestaurant(): bool
    {
        return $this->role === 'restaurant';
    }
}
