<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ride Fare Configuration
    |--------------------------------------------------------------------------
    */

    'base_fare' => env('RIDE_BASE_FARE', 15.00),
    'per_km_rate' => env('RIDE_PER_KM_RATE', 5.00),
    'per_minute_rate' => env('RIDE_PER_MINUTE_RATE', 1.50),
    'booking_fee' => env('RIDE_BOOKING_FEE', 5.00),
    'cancellation_fee' => env('RIDE_CANCELLATION_FEE', 10.00),
    
    'driver_search_radius' => env('DRIVER_SEARCH_RADIUS', 10), // kilometers
    
    'max_wait_time' => 10, // minutes before auto-cancelling
];
