<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ride extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'customer_id',
        'driver_id',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'pickup_terminal',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'dropoff_terminal',
        'vehicle_type',
        'distance_km',
        'estimated_duration_mins',
        'base_fare',
        'distance_fare',
        'time_fare',
        'booking_fee',
        'total_fare',
        'status',
        'requested_at',
        'driver_assigned_at',
        'driver_arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'customer_rating',
        'customer_review',
        'driver_rating',
        'driver_review',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'dropoff_lat' => 'decimal:8',
            'dropoff_lng' => 'decimal:8',
            'distance_km' => 'decimal:2',
            'base_fare' => 'decimal:2',
            'distance_fare' => 'decimal:2',
            'time_fare' => 'decimal:2',
            'booking_fee' => 'decimal:2',
            'total_fare' => 'decimal:2',
            'requested_at' => 'datetime',
            'driver_assigned_at' => 'datetime',
            'driver_arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ride) {
            if (empty($ride->booking_code)) {
                $ride->booking_code = 'SM' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Status Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSearching(): bool
    {
        return $this->status === 'searching';
    }

    public function isDriverAssigned(): bool
    {
        return $this->status === 'driver_assigned';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'searching', 'driver_assigned', 'driver_arriving']);
    }

    // Status Transitions
    public function assignDriver(int $driverId): void
    {
        $this->update([
            'driver_id' => $driverId,
            'status' => 'driver_assigned',
            'driver_assigned_at' => now(),
        ]);
    }

    public function markDriverArrived(): void
    {
        $this->update([
            'status' => 'driver_arriving',
            'driver_arrived_at' => now(),
        ]);
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(string $reason, string $cancelledBy): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);
    }
}
