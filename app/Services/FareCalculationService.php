<?php

namespace App\Services;

class FareCalculationService
{
    protected $baseFare;
    protected $perKmRate;
    protected $perMinuteRate;
    protected $bookingFee;

    public function __construct()
    {
        $this->baseFare = config('ride.base_fare', 15.00);
        $this->perKmRate = config('ride.per_km_rate', 5.00);
        $this->perMinuteRate = config('ride.per_minute_rate', 1.50);
        $this->bookingFee = config('ride.booking_fee', 5.00);
    }

    /**
     * Calculate fare for a ride
     */
    public function calculateFare(float $pickupLat, float $pickupLng, float $dropoffLat, float $dropoffLng): array
    {
        // Calculate distance in kilometers using Haversine formula
        $distanceKm = $this->calculateDistance($pickupLat, $pickupLng, $dropoffLat, $dropoffLng);
        
        // Estimate duration (assuming average speed of 40 km/h in city)
        $estimatedDurationMins = round(($distanceKm / 40) * 60);
        
        // Calculate fare components
        $distanceFare = $distanceKm * $this->perKmRate;
        $timeFare = $estimatedDurationMins * $this->perMinuteRate;
        $totalFare = $this->baseFare + $distanceFare + $timeFare + $this->bookingFee;

        return [
            'distance_km' => round($distanceKm, 2),
            'estimated_duration_mins' => max(5, $estimatedDurationMins), // Minimum 5 mins
            'base_fare' => $this->baseFare,
            'distance_fare' => round($distanceFare, 2),
            'time_fare' => round($timeFare, 2),
            'booking_fee' => $this->bookingFee,
            'total_fare' => round($totalFare, 2),
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Find nearby drivers within radius
     */
    public function findNearbyDrivers(float $lat, float $lng, float $radiusKm = 10): array
    {
        // This would typically use a spatial database query
        // For now, return empty array - implement with PostGIS or similar
        return [];
    }
}
