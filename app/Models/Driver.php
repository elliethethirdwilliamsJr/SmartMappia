<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'license_number',
        'vehicle_type',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'vehicle_color',
        'license_plate',
        'vehicle_photo',
        'status',
        'current_lat',
        'current_lng',
        'last_location_update',
        'is_verified',
        'verified_at',
        'total_trips',
        'total_earnings',
    ];

    protected function casts(): array
    {
        return [
            'current_lat' => 'decimal:8',
            'current_lng' => 'decimal:8',
            'total_earnings' => 'decimal:2',
            'last_location_update' => 'datetime',
            'verified_at' => 'datetime',
            'is_verified' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rides()
    {
        return $this->hasMany(Ride::class, 'driver_id', 'user_id');
    }

    // Helpers
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->is_verified;
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'last_location_update' => now(),
        ]);
    }

    public function setAvailable(): void
    {
        $this->update(['status' => 'available']);
    }

    public function setBusy(): void
    {
        $this->update(['status' => 'busy']);
    }

    public function setOffline(): void
    {
        $this->update(['status' => 'offline']);
    }
}
